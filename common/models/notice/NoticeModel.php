<?php

namespace common\models\notice;

use common\models\Error;
use common\models\LogicModel;
use common\models\system\User;
use common\realtime\NoticeRealtime;
use mylibs\yii\db\Transaction;

/**
 * 后台站内消息领域模型。
 *
 * 负责消息与接收记录的原子创建，以及接收人已读状态的幂等更新。
 */
final class NoticeModel extends LogicModel
{
    /** @var Notice 当前站内消息实体 */
    private Notice $message;

    /** 使用站内消息实体构造领域对象。 */
    public function __construct(Notice $message)
    {
        $this->message = $message;
        parent::__construct();
    }

    /**
     * 创建消息并为指定的可登录后台用户生成接收记录。
     *
     * @param array{type: int, title: string, content: string, extras?: array, createUserId?: int|null} $attributes 消息内容
     * @param int[] $recipientUserIds 接收后台用户ID
     * @return self|Error 创建成功返回领域对象，失败返回领域错误
     */
    public static function create(array $attributes, array $recipientUserIds): self|Error
    {
        $recipientUserIds = array_values(array_unique(array_filter(array_map('intval', $recipientUserIds))));
        if ($recipientUserIds === []) {
            return new Error('站内消息至少需要一名接收人。');
        }
        $validUserIds = User::find()
            ->select('id')
            ->where(['id' => $recipientUserIds, 'status' => User::STATUS_1000])
            ->column();
        $validUserIds = array_values(array_map('intval', $validUserIds));
        sort($validUserIds);
        $expectedUserIds = $recipientUserIds;
        sort($expectedUserIds);
        if ($validUserIds !== $expectedUserIds) {
            return new Error('部分站内消息接收人不存在或当前不可登录。');
        }

        $message = new Notice();
        $message->type = (int) $attributes['type'];
        $message->title = trim((string) $attributes['title']);
        $message->content = trim((string) $attributes['content']);
        $message->extras = self::encodeExtras($attributes['extras'] ?? []);
        $message->create_user_id = isset($attributes['createUserId'])
            ? (int) $attributes['createUserId']
            : null;

        $transaction = new Transaction();
        if (!$message->save()) {
            $transaction->rollback();
            return new Error($message->getErrors());
        }
        foreach ($validUserIds as $userId) {
            $recipient = new NoticeRecipient();
            $recipient->message_id = (int) $message->id;
            $recipient->user_id = $userId;
            if (!$recipient->save()) {
                $transaction->rollback();
                return new Error($recipient->getErrors());
            }
        }
        $transaction->commit();

        NoticeRealtime::publish($message, $validUserIds);
        return new self($message);
    }

    /** 按消息ID恢复站内消息领域对象。 */
    public static function getById(int $id): ?self
    {
        $message = Notice::findOne(['id' => $id]);
        return $message === null ? null : new self($message);
    }

    /**
     * 将当前消息对指定接收人幂等标记为已读。
     */
    public function markRead(int $userId): bool
    {
        $recipient = NoticeRecipient::findOne([
            'message_id' => $this->message->id,
            'user_id' => $userId,
        ]);
        if ($recipient === null) {
            return $this->setError('站内消息不存在或不属于当前用户。');
        }
        if ($recipient->isRead()) {
            return true;
        }
        NoticeRecipient::updateAll(
            ['read_time' => NoticeRecipient::expressionNow()],
            ['id' => $recipient->id, 'read_time' => null]
        );
        return true;
    }

    /** 将指定后台用户的全部未读站内消息幂等标记为已读。 */
    public static function markAllRead(int $userId): void
    {
        NoticeRecipient::updateAll(
            ['read_time' => NoticeRecipient::expressionNow()],
            ['user_id' => $userId, 'read_time' => null]
        );
    }

    /** 返回当前站内消息实体。 */
    public function getData(): Notice
    {
        return $this->message;
    }

    /** 将扩展参数编码为可持久化JSON，空参数保存为空。 */
    private static function encodeExtras(array $extras): ?string
    {
        if ($extras === []) {
            return null;
        }
        $json = json_encode($extras, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : null;
    }
}
