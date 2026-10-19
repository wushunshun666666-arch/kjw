<?php

/**
 * 后台客服应用服务。
 *
 * 连接后台控制器、表单和客服领域逻辑，统一输出会话与消息数据。
 */

namespace app\models\kefu;

use app\helpers\Url;
use app\models\Service;
use common\realtime\KefuRealtime;
use common\models\Error;
use common\models\kefu\KefuConversation;
use common\models\kefu\KefuMessage;
use common\models\kefu\KefuModel;
use common\models\kefu\KefuQuickReply;
use common\models\member\Member;
use common\models\system\User;
use common\resource\KefuMediaResource;
use Yii;

/** 客服后台管理应用服务。 */
final class KefuService extends Service
{
    /**
     * 返回工作台首次加载所需的全部会员列表及已有会话摘要。
     *
     * 左侧列表由浏览器在当前页面内筛选，避免每次输入关键词或收到消息都重新请求分页接口。
     *
     * @return array<int, array<string, int|string|null>> 客服工作台会员二维数组
     */
    public function getConversationItems(): array
    {
        $members = Member::find()
            ->select(['id', 'username', 'nickname', 'avatar', 'status'])
            ->with([
                'account' => static fn ($query) => $query->select([
                    'id', 'member_id', 'available_amount',
                ]),
            ])
            ->orderBy(['id' => SORT_DESC])
            ->all();
        $conversations = KefuConversation::find()
            ->select([
                'id', 'member_id', 'user_id', 'last_message_preview', 'last_message_time',
                'user_unread_count', 'create_time',
            ])
            ->with([
                'user' => static fn ($query) => $query->select(['id', 'name']),
            ])
            ->indexBy('member_id')
            ->all();

        return array_map(static function (Member $member) use ($conversations): array {
            $conversation = $conversations[$member->id] ?? null;
            return [
                'memberId' => (int) $member->id,
                'conversationId' => $conversation === null ? null : (int) $conversation->id,
                'username' => $member->username,
                'nickname' => $member->nickname,
                'avatar' => $member->avatar,
                'statusName' => $member->getStatusName(),
                'availableAmount' => $member->account?->available_amount ?: '0.0000',
                'ownerName' => $conversation?->user?->name ?: '无人接待',
                'unreadCount' => $conversation === null ? 0 : (int) $conversation->user_unread_count,
                'preview' => $conversation?->last_message_preview ?: '暂无消息',
                'lastMessageTime' => $conversation?->last_message_time ?: $conversation?->create_time,
            ];
        }, $members);
    }

    /**
     * 获取一页倒序消息，返回前再恢复为正序显示。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMessages(int $conversationId, ?int $beforeId = null): array
    {
        $query = KefuMessage::find()
            ->select([
                'id', 'conversation_id', 'sender_type', 'sender_member_id', 'sender_user_id',
                'message_type', 'content', 'media_url', 'media_mime', 'media_size',
                'media_duration', 'media_width', 'media_height', 'client_message_id', 'delivered_time', 'read_time',
                'create_time',
            ])
            ->with([
                'senderMember' => static fn ($query) => $query->select(['id', 'nickname']),
                'senderUser' => static fn ($query) => $query->select(['id', 'name']),
            ])
            ->where(['conversation_id' => $conversationId])
            ->orderBy(['id' => SORT_DESC])
            ->limit(50);
        if ($beforeId !== null) {
            $query->andWhere(['<', 'id', $beforeId]);
        }
        // 明确选择响应字段，禁止把后台用户密码哈希等关联字段序列化到浏览器。
        return $this->formatMessages(array_reverse($query->all()));
    }

    /**
     * 获取指定消息之后新增的一页消息，供实时事件触发后增量补齐聊天区域。
     *
     * @param int $conversationId 客服会话ID
     * @param int $afterId 当前浏览器已经渲染的最大消息ID
     * @return array<int, array<string, mixed>> 按消息ID正序排列的新增消息
     */
    public function getMessagesAfter(int $conversationId, int $afterId): array
    {
        return $this->formatMessages(KefuMessage::find()
            ->select([
                'id', 'conversation_id', 'sender_type', 'sender_member_id', 'sender_user_id',
                'message_type', 'content', 'media_url', 'media_mime', 'media_size',
                'media_duration', 'media_width', 'media_height', 'client_message_id', 'delivered_time', 'read_time',
                'create_time',
            ])
            ->with([
                'senderMember' => static fn ($query) => $query->select(['id', 'nickname']),
                'senderUser' => static fn ($query) => $query->select(['id', 'name']),
            ])
            ->where(['conversation_id' => $conversationId])
            ->andWhere(['>', 'id', $afterId])
            ->orderBy(['id' => SORT_ASC])
            ->limit(50)
            ->all());
    }

    /**
     * 返回指定自然日的历史聊天消息，供客服从右侧历史摘要定位到中间聊天区。
     *
     * @param int $conversationId 客服会话ID
     * @param string $date 自然日，格式为 YYYY-MM-DD
     * @return array<int, array<string, mixed>> 按发送顺序排列的历史消息
     */
    public function getHistoryMessages(int $conversationId, string $date): array
    {
        return $this->formatMessages(KefuMessage::find()
            ->select([
                'id', 'conversation_id', 'sender_type', 'sender_member_id', 'sender_user_id',
                'message_type', 'content', 'media_url', 'media_mime', 'media_size',
                'media_duration', 'media_width', 'media_height', 'client_message_id', 'delivered_time', 'read_time',
                'create_time',
            ])
            ->with([
                'senderMember' => static fn ($query) => $query->select(['id', 'nickname']),
                'senderUser' => static fn ($query) => $query->select(['id', 'name']),
            ])
            ->where(['conversation_id' => $conversationId])
            ->andWhere(['>=', 'create_time', $date . ' 00:00:00'])
            ->andWhere(['<', 'create_time', date('Y-m-d', strtotime($date . ' +1 day')) . ' 00:00:00'])
            ->orderBy(['id' => SORT_ASC])
            ->limit(500)
            ->all());
    }

    /**
     * 将客服消息实体转换为后台页面允许消费的安全响应字段。
     *
     * @param array<int, KefuMessage> $messages 消息实体列表
     * @return array<int, array<string, mixed>> 页面消息二维数组
     */
    private function formatMessages(array $messages): array
    {
        return array_map(fn (KefuMessage $message): array => [
            'id' => (int) $message->id,
            'clientMessageId' => $message->client_message_id,
            'senderType' => (int) $message->sender_type,
            'senderName' => $message->sender_type === KefuMessage::SENDER_MEMBER
                ? ($message->senderMember?->nickname ?: '会员')
                : ($message->senderUser?->name ?: '系统'),
            'messageType' => (int) $message->message_type,
            'content' => $message->content,
            'mediaUrl' => $message->media_url === null
                ? null
                : $this->getActionUrl('media', ['key' => $message->media_url]),
            'mediaMime' => $message->media_mime,
            'mediaSize' => $message->media_size === null ? null : (int) $message->media_size,
            'mediaDuration' => $message->media_duration === null ? null : (int) $message->media_duration,
            'mediaWidth' => $message->media_width === null ? null : (int) $message->media_width,
            'mediaHeight' => $message->media_height === null ? null : (int) $message->media_height,
            'deliveredTime' => $message->delivered_time,
            'readTime' => $message->read_time,
            'createTime' => $message->create_time,
        ], $messages);
    }

    /** 获取会话及右侧会员详情。 */
    public function getConversation(int $id): KefuConversation|false
    {
        $conversation = KefuConversation::find()
            ->select([
                'id', 'member_id', 'user_id', 'status', 'member_unread_count', 'user_unread_count',
            ])
            ->with([
                'member' => static fn ($query) => $query
                    ->select([
                        'id', 'parent_id', 'invite_code', 'username', 'nickname', 'avatar',
                        'mobile', 'email', 'status', 'register_type',
                    ])
                    ->with([
                        'account' => static fn ($accountQuery) => $accountQuery
                            ->select(['id', 'member_id', 'available_amount', 'frozen_amount']),
                        'parent' => static fn ($parentQuery) => $parentQuery
                            ->select(['id', 'nickname']),
                        'authIdentities' => static fn ($identityQuery) => $identityQuery
                            ->select([
                                'id', 'member_id', 'provider', 'provider_user_id', 'nickname',
                                'status', 'last_login_time', 'create_time',
                            ]),
                    ]),
                'user' => static fn ($query) => $query->select(['id', 'name']),
            ])
            ->where(['kefu_conversation.id' => $id])
            ->one();
        if ($conversation === null) {
            $this->setError('客服会话不存在。');
            return false;
        }
        return $conversation;
    }

    /**
     * 获取客服工作台需要的安全会话和会员详情字段。
     *
     * @return array<string, mixed>|false
     */
    public function getConversationData(int $id): array|false
    {
        $conversation = $this->getConversation($id);
        if ($conversation === false) {
            return false;
        }
        $member = $conversation->member;
        $currentUserId = (int) self::getUserId();
        return [
            'id' => (int) $conversation->id,
            'status' => (int) $conversation->status,
            'statusName' => $conversation->getStatusName(),
            'userId' => $conversation->user_id === null ? null : (int) $conversation->user_id,
            'userName' => $conversation->user?->name,
            'canSend' => (int) $conversation->status === KefuConversation::STATUS_SERVING
                && (int) $conversation->user_id === $currentUserId,
            'memberUnreadCount' => (int) $conversation->member_unread_count,
            'userUnreadCount' => (int) $conversation->user_unread_count,
            'member' => [
                'id' => (int) $member->id,
                'username' => $member->username,
                'nickname' => $member->nickname,
                'avatar' => $member->avatar,
                'mobile' => $member->mobile,
                'email' => $member->email,
                'status' => (int) $member->status,
                'statusName' => $member->getStatusName(),
                'registerTypeName' => $member->getRegisterTypeName(),
                'inviteCode' => $member->invite_code,
                'parentId' => $member->parent_id === null ? null : (int) $member->parent_id,
                'parentName' => $member->parent?->nickname,
                'availableAmount' => $member->account?->available_amount,
                'frozenAmount' => $member->account?->frozen_amount,
            ],
            'history' => $this->getHistorySummaries((int) $conversation->id),
        ];
    }

    /**
     * 把永久消息按自然日归并为右栏历史记录摘要。
     *
     * @return array<int, array{date: string, count: int, preview: string}>
     */
    private function getHistorySummaries(int $conversationId): array
    {
        $summaries = KefuMessage::find()
            ->select([
                'date' => new \yii\db\Expression('DATE(`create_time`)'),
                'count' => new \yii\db\Expression('COUNT(*)'),
                'lastMessageId' => new \yii\db\Expression('MAX(`id`)'),
            ])
            ->where(['conversation_id' => $conversationId])
            ->groupBy(new \yii\db\Expression('DATE(`create_time`)'))
            ->orderBy(['date' => SORT_DESC])
            ->asArray()
            ->all();
        if ($summaries === []) {
            return [];
        }
        $lastMessageIds = array_column($summaries, 'lastMessageId');
        $lastMessages = KefuMessage::find()
            ->select(['id', 'message_type', 'content'])
            ->where(['id' => $lastMessageIds])
            ->indexBy('id')
            ->all();

        return array_map(static function (array $summary) use ($lastMessages): array {
            $message = $lastMessages[(int) $summary['lastMessageId']] ?? null;
            return [
                'date' => (string) $summary['date'],
                'count' => (int) $summary['count'],
                'preview' => $message?->getPreview() ?? '暂无消息',
            ];
        }, $summaries);
    }

    /** 主动联系会员并自动领取可用会话。 */
    public function initiate(int $memberId): KefuConversation|false
    {
        $result = KefuModel::getOrCreate($memberId);
        if ($result instanceof Error) {
            $this->addErrors($result->getErrors());
            return false;
        }
        $conversation = $result->getData();
        if ($conversation->user_id === null && !$result->claim()) {
            $this->addErrors($result->getErrors());
            return false;
        }
        return $conversation;
    }

    /** 领取指定会话。 */
    public function claim(int $id): bool
    {
        return $this->executeModelAction($id, static fn (KefuModel $model): bool => $model->claim());
    }

    /** 转交指定会话。 */
    public function transfer(int $id, int $targetUserId): bool
    {
        return $this->executeModelAction(
            $id,
            static fn (KefuModel $model): bool => $model->transfer($targetUserId)
        );
    }

    /** 结束并释放指定会话。 */
    public function end(int $id): bool
    {
        return $this->executeModelAction($id, static fn (KefuModel $model): bool => $model->end());
    }

    /** 当前客服发送消息。 */
    public function send(int $id, SendForm $form): KefuMessage|false
    {
        $model = $this->getModel($id);
        if ($model === false) {
            return false;
        }
        // 媒体属性只能由服务端根据安全媒体标识生成。
        $mediaResource = new KefuMediaResource();
        $attributes = $mediaResource->buildMessageAttributes($form->getSafeAttributes());
        if ($attributes === false) {
            $this->addErrors($mediaResource->getErrors());
            return false;
        }
        $result = $model->sendUserMessage($attributes);
        if ($result instanceof Error) {
            $this->addErrors($result->getErrors());
            return false;
        }
        if (
            (int) $form->messageType !== KefuMessage::TYPE_TEXT
            && !$mediaResource->migrate((string) $form->mediaKey)
        ) {
            $this->addErrors($mediaResource->getErrors());
            return false;
        }
        return $result;
    }

    /** 清零当前客服的会员消息未读数。 */
    public function markRead(int $id, int $lastVisibleMessageId): bool
    {
        return $this->executeModelAction(
            $id,
            static fn (KefuModel $model): bool => $model->markUserRead($lastVisibleMessageId)
        );
    }

    /**
     * 返回所有启用的公共快捷回复。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getQuickReplies(): array
    {
        return KefuQuickReply::find()
            ->select(['id', 'title', 'content'])
            ->where(['status' => KefuQuickReply::STATUS_ACTIVE])
            ->orderBy(['orderby' => SORT_ASC, 'id' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * 返回可作为转交目标的有效后台用户，并排除当前登录客服。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableUsers(): array
    {
        return User::find()
            ->select(['user.id', 'user.name'])
            ->distinct()
            ->leftJoin('user_role', 'user_role.user_id = user.id')
            ->leftJoin('role_module', 'role_module.role_id = user_role.role_id')
            ->leftJoin('module', 'module.id = role_module.module_id')
            ->where(['user.status' => User::STATUS_1000])
            ->andWhere(['<>', 'user.id', (int) self::getUserId()])
            ->andWhere([
                'or',
                ['user.id' => User::ADMIN_ID],
                ['module.url' => 'kefu/kefu/index'],
            ])
            ->orderBy(['user.name' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /** 返回会员表当前总人数。 */
    public function getMemberTotal(): int
    {
        return (int) Member::find()->count();
    }

    /**
     * 返回工作台前端所需的安全配置。
     *
     * @param array<int, array<string, int|string|null>> $conversationItems 左侧会员二维数组
     * @param array<int, array<string, mixed>> $quickReplies 快捷回复列表
     * @param array<int, array<string, mixed>> $availableUsers 可转交客服列表
     * @param int $initialMemberId 进入工作台后需要自动打开的会员ID
     * @return array<string, mixed>
     */
    public function getWorkbenchConfig(
        array $conversationItems,
        array $quickReplies,
        array $availableUsers,
        int $initialMemberId = 0
    ): array
    {
        return [
            'conversations' => $conversationItems,
            'initialMemberId' => $initialMemberId,
            'urls' => [
                'conversation' => $this->getActionUrl('conversation'),
                'messages' => $this->getActionUrl('messages'),
                'initiate' => $this->getActionUrl('initiate'),
                'claim' => $this->getActionUrl('claim'),
                'transfer' => $this->getActionUrl('transfer'),
                'end' => $this->getActionUrl('end'),
                'send' => $this->getActionUrl('send'),
                'upload' => $this->getActionUrl('upload'),
                'markRead' => $this->getActionUrl('mark-read'),
            ],
            'quickReplies' => array_map(static fn (array $item): array => [
                'id' => (int) $item['id'],
                'title' => (string) $item['title'],
                'content' => (string) $item['content'],
            ], $quickReplies),
            'users' => array_map(static fn (array $user): array => [
                'id' => (int) $user['id'],
                'name' => (string) $user['name'],
            ], $availableUsers),
            'messageTypes' => [
                'text' => KefuMessage::TYPE_TEXT,
                'image' => KefuMessage::TYPE_IMAGE,
                'audio' => KefuMessage::TYPE_AUDIO,
                'video' => KefuMessage::TYPE_VIDEO,
                'system' => KefuMessage::TYPE_SYSTEM,
            ],
            'acceptedMediaTypes' => implode(',', [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp',
                'audio/mpeg', 'audio/mp4', 'audio/ogg', 'audio/webm',
                'video/mp4', 'video/webm', 'video/quicktime',
            ]),
            'emojis' => [
                '😀', '😊', '😄', '😁', '😆', '😅', '🤣', '😂',
                '🙂', '😉', '😍', '🥰', '😘', '😗', '😚', '😋',
                '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭', '🤫',
                '🤔', '🤐', '🤨', '😐', '😑', '😶', '😏', '😒',
                '🙄', '😬', '😮', '😯', '😲', '😱', '😳', '🥺',
                '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😴',
                '🤤', '😷', '🤒', '🤕', '🤠', '😎', '🤓', '🧐',
                '😕', '😟', '🙁', '👍', '👎', '👌', '✌️', '🤞',
                '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝️',
                '✋', '🤚', '🖐️', '💪', '🙏', '👏', '🤝', '🙌',
                '👐', '🤲', '🤜', '❤️', '🧡', '💛', '💚', '💙',
                '💜', '🖤', '🤍',
            ],
            'realtime' => KefuRealtime::createAdminConnection(
                (int) self::getUserId(),
                (string) self::getUserName()
            ),
        ];
    }

    /**
     * 生成客服异步接口地址。
     *
     * @param string $action 当前控制器的动作ID
     * @param array<string, string> $parameters 查询参数
     * @return string 可直接请求的客服接口地址
     */
    private function getActionUrl(string $action, array $parameters = []): string
    {
        return Url::to(array_merge([$action], $parameters));
    }

    /** 获取领域对象。 */
    private function getModel(int $id): KefuModel|false
    {
        $model = KefuModel::getById($id);
        if ($model === null) {
            $this->setError('客服会话不存在。');
            return false;
        }
        return $model;
    }

    /** 执行返回布尔值的领域动作并复制业务错误。 */
    private function executeModelAction(int $id, callable $action): bool
    {
        $model = $this->getModel($id);
        if ($model === false) {
            return false;
        }
        if (!$action($model)) {
            return $this->addErrors($model->getErrors());
        }
        return true;
    }
}
