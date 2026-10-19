<?php

namespace common\realtime;

use common\models\notice\Notice;
use mylibs\yii\config\Configure;
use Throwable;
use Yii;

/**
 * 后台站内消息实时通知发布器。
 *
 * 数据库是消息事实来源，Redis只负责提示在线后台刷新消息，不承担消息持久化。
 */
final class NoticeRealtime
{
    /**
     * 在数据库事务提交后发布消息提醒，Redis不可用不影响已持久化消息。
     *
     * @param Notice $message 已成功入库的消息
     * @param int[] $recipientUserIds 接收后台用户ID
     */
    public static function publish(Notice $message, array $recipientUserIds): void
    {
        $payload = json_encode([
            'type' => 'admin.message',
            'messageId' => (int) $message->id,
            'messageType' => (int) $message->type,
            'title' => (string) $message->title,
            'content' => (string) $message->content,
            'createTime' => (string) $message->create_time,
            'targetUserIds' => array_values(array_map('intval', $recipientUserIds)),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            Yii::warning('后台站内消息实时通知编码失败。', __METHOD__);
            return;
        }
        try {
            $config = (new Configure())->loadCommonConfig('message-center');
            Yii::$app->redis->publish((string) $config['redisChannel'], $payload);
        } catch (Throwable $exception) {
            Yii::warning('后台站内消息已经入库，但实时提醒发布失败：' . $exception->getMessage(), __METHOD__);
        }
    }
}
