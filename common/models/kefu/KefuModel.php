<?php

/**
 * 客服会话领域逻辑模型。
 *
 * 负责会话创建、并发领取、转交、结束、消息发送及未读状态变更。
 */

namespace common\models\kefu;

use common\realtime\KefuRealtime;
use common\models\Error;
use common\models\LogicModel;
use common\models\member\Member;
use common\models\system\User;
use mylibs\yii\db\Transaction;
use Yii;
use yii\db\Expression;

/**
 * 客服会话领域服务对象。
 */
final class KefuModel extends LogicModel
{
    /** @var KefuConversation 当前处理的长期会话 */
    private KefuConversation $conversation;

    /**
     * 构造客服会话领域对象。
     *
     * @param KefuConversation $conversation 当前长期会话
     */
    public function __construct(KefuConversation $conversation)
    {
        $this->conversation = $conversation;
        parent::__construct();
    }

    /**
     * 按会话ID获取领域对象。
     *
     * @param int $id 客服会话ID
     */
    public static function getById(int $id): ?self
    {
        // 查询指定会话，未找到时由调用层统一返回不存在错误。
        $conversation = KefuConversation::findOne(['id' => $id]);
        return $conversation === null ? null : new self($conversation);
    }

    /**
     * 获取会员长期会话，不存在时创建等待接待会话。
     *
     * @param int $memberId 会员ID
     * @return self|Error 成功返回领域对象，会员不存在或保存失败时返回错误
     */
    public static function getOrCreate(int $memberId): self|Error
    {
        // 已有会话直接复用，保证左侧客户列表不会重复。
        $conversation = KefuConversation::findOne(['member_id' => $memberId]);
        if ($conversation !== null) {
            return new self($conversation);
        }
        if (Member::findById($memberId) === null) {
            return new Error('会员不存在。', 'member_id');
        }

        // 新会话默认无人接待且双方未读数均为零。
        $transaction = new Transaction();
        $conversation = new KefuConversation();
        $conversation->member_id = $memberId;
        $conversation->user_id = null;
        $conversation->status = KefuConversation::STATUS_WAITING;
        $conversation->last_message_preview = '';
        $conversation->member_unread_count = 0;
        $conversation->user_unread_count = 0;
        if (!$conversation->save()) {
            $transaction->rollback();
            return new Error($conversation->getErrors());
        }

        $transaction->commit();
        return new self($conversation);
    }

    /**
     * 原子领取无人接待的会话。
     *
     */
    public function claim(): bool
    {
        $userId = (int) self::getUserId();
        if (!$this->isAvailableUser($userId)) {
            return $this->setError('客服用户不存在或当前不可接待。');
        }
        if (
            $this->conversation->status === KefuConversation::STATUS_SERVING
            && (int) $this->conversation->user_id === $userId
        ) {
            return true;
        }

        // 条件更新保证多个客服并发领取时只有一个成功。
        $transaction = new Transaction();
        $affected = KefuConversation::updateAll(
            [
                'user_id' => $userId,
                'status' => KefuConversation::STATUS_SERVING,
                'update_time' => KefuConversation::expressionNow(),
            ],
            [
                'and',
                ['id' => $this->conversation->id, 'user_id' => null],
                ['in', 'status', [KefuConversation::STATUS_WAITING, KefuConversation::STATUS_ENDED]],
            ]
        );
        if ($affected !== 1) {
            $transaction->rollback();
            return $this->setError('会话当前正由其他客服接待。');
        }

        $this->conversation->user_id = $userId;
        $this->conversation->status = KefuConversation::STATUS_SERVING;
        if (!$this->saveSystemMessage($userId, '客服开始接待')) {
            $transaction->rollback();
            return false;
        }
        $transaction->commit();
        $this->publishAgentAssignment();
        return true;
    }

    /**
     * 将会话转交给另一名有效客服。
     *
     * @param int $targetUserId 目标客服ID
     */
    public function transfer(int $targetUserId): bool
    {
        $operatorUserId = (int) self::getUserId();
        $force = $this->isCurrentOperatorAdmin();
        if ($targetUserId === $operatorUserId) {
            return $this->setError('不能将会话转交给自己。');
        }
        if (!$this->isAvailableUser($targetUserId)) {
            return $this->setError('目标客服不存在或当前不可接待。');
        }
        if (!$force && (int) $this->conversation->user_id !== $operatorUserId) {
            return $this->setError('只能转交自己正在接待的会话。');
        }
        if ((int) $this->conversation->user_id === $targetUserId) {
            return $this->setError('目标客服已经在接待该会员。');
        }

        // 状态变化和系统消息必须处于同一事务，避免出现无记录的转交。
        $transaction = new Transaction();
        $oldUserId = $this->conversation->user_id;
        $affected = KefuConversation::updateAll(
            ['user_id' => $targetUserId, 'update_time' => KefuConversation::expressionNow()],
            [
                'id' => $this->conversation->id,
                'user_id' => $oldUserId,
                'status' => KefuConversation::STATUS_SERVING,
            ]
        );
        if ($affected !== 1) {
            $transaction->rollback();
            return $this->setError('会话接待状态已经变化，请刷新后重试。');
        }

        $this->conversation->user_id = $targetUserId;
        if (!$this->saveSystemMessage($operatorUserId, '客服已将会话转交')) {
            $transaction->rollback();
            return false;
        }
        $transaction->commit();
        $this->publishAgentAssignment();
        return true;
    }

    /**
     * 结束当前接待并释放客服归属。
     *
     */
    public function end(): bool
    {
        $operatorUserId = (int) self::getUserId();
        $force = $this->isCurrentOperatorAdmin();
        if (!$force && (int) $this->conversation->user_id !== $operatorUserId) {
            return $this->setError('只能结束自己正在接待的会话。');
        }

        // 结束状态与系统消息在一个事务内提交。
        $transaction = new Transaction();
        $oldUserId = $this->conversation->user_id;
        $affected = KefuConversation::updateAll(
            [
                'user_id' => null,
                'status' => KefuConversation::STATUS_ENDED,
                'update_time' => KefuConversation::expressionNow(),
            ],
            ['id' => $this->conversation->id, 'user_id' => $oldUserId]
        );
        if ($affected !== 1) {
            $transaction->rollback();
            return $this->setError('会话接待状态已经变化，请刷新后重试。');
        }

        $this->conversation->user_id = null;
        $this->conversation->status = KefuConversation::STATUS_ENDED;
        if (!$this->saveSystemMessage($operatorUserId, '客服结束接待')) {
            $transaction->rollback();
            return false;
        }
        $transaction->commit();
        $this->publishAgentAssignment();
        return true;
    }

    /**
     * 保存会员发送的文字或媒体消息；结束的会话自动重新进入等待队列。
     *
     * @param int $memberId 发送会员ID
     * @param array<string, mixed> $attributes 消息内容和媒体元数据
     * @return KefuMessage|Error 消息保存结果
     */
    public function sendMemberMessage(int $memberId, array $attributes): KefuMessage|Error
    {
        if ((int) $this->conversation->member_id !== $memberId) {
            return new Error('不能向其他会员的客服会话发送消息。');
        }

        return $this->sendMessage(KefuMessage::SENDER_MEMBER, $memberId, $attributes);
    }

    /**
     * 保存当前接待客服发送的文字或媒体消息。
     *
     * @param array<string, mixed> $attributes 消息内容和媒体元数据
     * @return KefuMessage|Error 消息保存结果
     */
    public function sendUserMessage(array $attributes): KefuMessage|Error
    {
        $userId = (int) self::getUserId();
        if (
            $this->conversation->status !== KefuConversation::STATUS_SERVING
            || (int) $this->conversation->user_id !== $userId
        ) {
            return new Error('只有当前接待客服可以发送消息。');
        }

        return $this->sendMessage(KefuMessage::SENDER_USER, $userId, $attributes);
    }

    /**
     * 将会员发来的消息标记为当前客服已读。
     *
     * @param int $lastVisibleMessageId 当前可视区域最后一条消息ID
     */
    public function markUserRead(int $lastVisibleMessageId): bool
    {
        $userId = (int) self::getUserId();
        if ((int) $this->conversation->user_id !== $userId) {
            return $this->setError('只有当前接待客服可以更新已读状态。');
        }

        $transaction = new Transaction();
        $readCount = KefuMessage::updateAll(
            [
                'delivered_time' => KefuMessage::expressionNow(),
                'read_time' => KefuMessage::expressionNow(),
            ],
            ['and', [
                'conversation_id' => $this->conversation->id,
                'sender_type' => KefuMessage::SENDER_MEMBER,
                'read_time' => null,
            ], ['<=', 'id', $lastVisibleMessageId]]
        );
        $affected = KefuConversation::updateAll(
            [
                'user_unread_count' => new Expression(
                    'CASE WHEN user_unread_count >= :readCount THEN user_unread_count - :readCount ELSE 0 END',
                    [':readCount' => $readCount]
                ),
                'update_time' => KefuConversation::expressionNow(),
            ],
            ['id' => $this->conversation->id]
        );
        if ($affected !== 1) {
            $transaction->rollback();
            return $this->setError('会话已读状态已经变化，请刷新后重试。');
        }
        $transaction->commit();
        $this->conversation->refresh();

        // 通知会员：其发送的消息已被客服阅读
        if ($readCount > 0) {
            KefuRealtime::publishMemberRead(
                (int) $this->conversation->id,
                (int) $this->conversation->member_id,
                $userId,
                $lastVisibleMessageId
            );
        }
        return true;
    }

    /**
     * 将客服发来的消息标记为会员已读，正常和冻结会员均可调用。
     *
     * @param int $memberId 当前会员ID
     * @param int $lastVisibleMessageId 当前可视区域最后一条消息ID
     */
    public function markMemberRead(int $memberId, int $lastVisibleMessageId): bool
    {
        if ((int) $this->conversation->member_id !== $memberId) {
            return $this->setError('不能更新其他会员会话的已读状态。');
        }

        $userId = (int) $this->conversation->user_id;
        $transaction = new Transaction();
        $readCount = KefuMessage::updateAll(
            [
                'delivered_time' => KefuMessage::expressionNow(),
                'read_time' => KefuMessage::expressionNow(),
            ],
            ['and', [
                'conversation_id' => $this->conversation->id,
                'sender_type' => KefuMessage::SENDER_USER,
                'read_time' => null,
            ], ['<=', 'id', $lastVisibleMessageId]]
        );
        $affected = KefuConversation::updateAll(
            [
                'member_unread_count' => new Expression(
                    'CASE WHEN member_unread_count >= :readCount THEN member_unread_count - :readCount ELSE 0 END',
                    [':readCount' => $readCount]
                ),
                'update_time' => KefuConversation::expressionNow(),
            ],
            ['id' => $this->conversation->id]
        );
        if ($affected !== 1) {
            $transaction->rollback();
            return $this->setError('会话已读状态已经变化，请刷新后重试。');
        }
        $transaction->commit();
        $this->conversation->refresh();
        if ($readCount > 0 && $userId > 0) {
            KefuRealtime::publishMemberRead(
                (int) $this->conversation->id,
                $memberId,
                $userId,
                $lastVisibleMessageId
            );
        }
        return true;
    }

    /**
     * 根据已认证 WebSocket 接收方记录单条消息送达回执。
     *
     * @param int $messageId 消息ID
     * @param string $platform 接收方平台，A为客服、M为会员
     * @param int $identityId 接收方身份ID
     */
    public static function acknowledgeDelivery(int $messageId, string $platform, int $identityId): bool
    {
        $query = KefuMessage::find()->alias('message')
            ->innerJoin('kefu_conversation conversation', 'conversation.id = message.conversation_id')
            ->where(['message.id' => $messageId, 'message.delivered_time' => null]);
        if ($platform === 'M') {
            $query->andWhere([
                'message.sender_type' => KefuMessage::SENDER_USER,
                'conversation.member_id' => $identityId,
            ]);
        } elseif ($platform === 'A') {
            $query->andWhere([
                'message.sender_type' => KefuMessage::SENDER_MEMBER,
                'conversation.user_id' => $identityId,
            ]);
        } else {
            return false;
        }
        $message = $query->select(['message.id'])->one();
        return $message !== null && KefuMessage::updateAll(
            ['delivered_time' => KefuMessage::expressionNow()],
            ['id' => $messageId, 'delivered_time' => null]
        ) === 1;
    }

    /** 返回当前会话实体。 */
    public function getData(): KefuConversation
    {
        return $this->conversation;
    }

    /**
     * 校验并事务保存一条会员或客服消息。
     *
     * @param int $senderType 发送者类型
     * @param int $senderId 发送者会员ID或客服ID
     * @param array<string, mixed> $attributes 消息属性
     * @return KefuMessage|Error 消息保存结果
     */
    private function sendMessage(int $senderType, int $senderId, array $attributes): KefuMessage|Error
    {
        $messageType = (int) ($attributes['message_type'] ?? 0);
        $validationError = $this->validateMessage($messageType, $attributes);
        if ($validationError !== null) {
            return $validationError;
        }

        $clientMessageId = trim((string) ($attributes['client_message_id'] ?? ''));
        if ($clientMessageId === '') {
            return new Error('缺少客户端消息编号。', 'client_message_id');
        }
        $existingMessage = KefuMessage::findOne([
            'conversation_id' => $this->conversation->id,
            'client_message_id' => $clientMessageId,
        ]);
        if ($existingMessage !== null) {
            return $existingMessage;
        }

        $transaction = new Transaction();
        // 已结束会话收到会员消息时重新进入等待队列。
        // 标记会员是否正在通过新消息重新开启已结束的会话。
        $isMemberReopening = $senderType === KefuMessage::SENDER_MEMBER
            && $this->conversation->status === KefuConversation::STATUS_ENDED;
        if ($isMemberReopening) {
            $reopened = KefuConversation::updateAll(
                ['status' => KefuConversation::STATUS_WAITING, 'user_id' => null],
                ['id' => $this->conversation->id, 'status' => KefuConversation::STATUS_ENDED]
            );
            if ($reopened !== 1) {
                $transaction->rollback();
                return new Error('会话状态已经变化，请刷新后重试。');
            }
            $this->conversation->status = KefuConversation::STATUS_WAITING;
            $this->conversation->user_id = null;
        }

        $message = new KefuMessage();
        $message->conversation_id = $this->conversation->id;
        $message->sender_type = $senderType;
        $message->sender_member_id = $senderType === KefuMessage::SENDER_MEMBER ? $senderId : null;
        $message->sender_user_id = $senderType === KefuMessage::SENDER_USER ? $senderId : null;
        $message->message_type = $messageType;
        $message->content = $messageType === KefuMessage::TYPE_TEXT ? trim((string) $attributes['content']) : null;
        $message->media_url = $attributes['media_url'] ?? null;
        $message->media_mime = $attributes['media_mime'] ?? null;
        $message->media_size = $attributes['media_size'] ?? null;
        $message->media_duration = $attributes['media_duration'] ?? null;
        $message->media_width = $attributes['media_width'] ?? null;
        $message->media_height = $attributes['media_height'] ?? null;
        $message->client_message_id = $clientMessageId;
        if (!$message->save()) {
            $transaction->rollback();
            return new Error($message->getErrors());
        }
        if (!$message->refresh()) {
            $transaction->rollback();
            return new Error('消息发送时间读取失败，请重试。');
        }

        $unreadField = $senderType === KefuMessage::SENDER_MEMBER
            ? 'user_unread_count'
            : 'member_unread_count';
        $updated = KefuConversation::updateAll(
            [
                'last_message_id' => $message->id,
                'last_message_type' => $message->message_type,
                'last_message_preview' => $message->getPreview(),
                'last_message_time' => $message->create_time,
                $unreadField => new Expression($unreadField . ' + 1'),
                'update_time' => KefuConversation::expressionNow(),
            ],
            ['id' => $this->conversation->id]
        );
        if ($updated !== 1) {
            $transaction->rollback();
            return new Error('会话摘要更新失败，请刷新后重试。');
        }
        $transaction->commit();
        KefuRealtime::publishMessage($message, (int) $this->conversation->member_id);
        return $message;
    }

    /**
     * 校验文字内容或媒体白名单、大小和时长。
     *
     * @param int $messageType 消息类型
     * @param array<string, mixed> $attributes 消息属性
     */
    private function validateMessage(int $messageType, array $attributes): ?Error
    {
        if ($messageType === KefuMessage::TYPE_TEXT) {
            $content = trim((string) ($attributes['content'] ?? ''));
            if ($content === '' || mb_strlen($content) > 5000) {
                return new Error('文字消息长度必须在1至5000个字符之间。', 'content');
            }
            return null;
        }

        $limits = [
            KefuMessage::TYPE_IMAGE => [['image/jpeg', 'image/png', 'image/gif', 'image/webp'], 10 * 1024 * 1024, null],
            KefuMessage::TYPE_AUDIO => [['audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/webm'], 20 * 1024 * 1024, 300],
            KefuMessage::TYPE_VIDEO => [['video/mp4', 'video/webm', 'video/quicktime'], 100 * 1024 * 1024, 600],
        ];
        if (!isset($limits[$messageType])) {
            return new Error('不支持该消息类型。', 'message_type');
        }

        [$allowedMime, $maxSize, $maxDuration] = $limits[$messageType];
        $mediaUrl = trim((string) ($attributes['media_url'] ?? ''));
        $mediaMime = strtolower(trim((string) ($attributes['media_mime'] ?? '')));
        $mediaSize = (int) ($attributes['media_size'] ?? 0);
        $mediaDuration = (int) ($attributes['media_duration'] ?? 0);
        if ($mediaUrl === '' || !in_array($mediaMime, $allowedMime, true)) {
            return new Error('媒体文件格式不允许。', 'media_mime');
        }
        if ($mediaSize < 1 || $mediaSize > $maxSize) {
            return new Error('媒体文件大小超过限制。', 'media_size');
        }
        if ($maxDuration !== null && ($mediaDuration < 1 || $mediaDuration > $maxDuration)) {
            return new Error('媒体文件时长超过限制。', 'media_duration');
        }

        return null;
    }

    /** 保存接待过程系统消息。 */
    private function saveSystemMessage(int $operatorUserId, string $content): bool
    {
        $message = new KefuMessage();
        $message->conversation_id = $this->conversation->id;
        $message->sender_type = KefuMessage::SENDER_SYSTEM;
        $message->sender_user_id = $operatorUserId;
        $message->message_type = KefuMessage::TYPE_SYSTEM;
        $message->content = $content;
        $message->client_message_id = 'system-' . Yii::$app->security->generateRandomString(32);
        if (!$message->save()) {
            return $this->addErrors($message->getErrors());
        }

        KefuConversation::updateAll(
            [
                'last_message_id' => $message->id,
                'last_message_type' => $message->message_type,
                'last_message_preview' => $message->getPreview(),
                'last_message_time' => $message->create_time,
                'update_time' => new Expression('NOW()'),
            ],
            ['id' => $this->conversation->id]
        );
        return true;
    }

    /**
     * 向会员推送当前接待客服变更信息。
     *
     * 在领取、转交、结束等事务提交后调用，确保会员端实时感知接待状态。
     */
    private function publishAgentAssignment(): void
    {
        $userId = $this->conversation->user_id;
        $userName = '';
        if ($userId !== null) {
            $user = User::findById((int) $userId);
            if ($user !== null) {
                $userName = (string) $user->name;
            }
        }

        KefuRealtime::publishAgentAssignment(
            (int) $this->conversation->id,
            (int) $this->conversation->member_id,
            $userId,
            $userName,
            (int) $this->conversation->status
        );
    }

    /** 判断后台用户是否处于可接待状态。 */
    private function isAvailableUser(int $userId): bool
    {
        return User::find()
            ->alias('user')
            ->leftJoin('user_role', 'user_role.user_id = user.id')
            ->leftJoin('role_module', 'role_module.role_id = user_role.role_id')
            ->leftJoin('module', 'module.id = role_module.module_id')
            ->where(['user.id' => $userId, 'user.status' => User::STATUS_1000])
            ->andWhere([
                'or',
                ['user.id' => User::ADMIN_ID],
                ['module.url' => 'kefu/kefu/index'],
            ])
            ->exists();
    }

    /** 判断当前领域操作人是否为后台超级管理员。 */
    private function isCurrentOperatorAdmin(): bool
    {
        $identity = self::getIdentity();
        if (!is_object($identity) || !method_exists($identity, 'getUser')) {
            return false;
        }

        $user = $identity->getUser();
        return $user instanceof User && $user->isAdmin();
    }
}
