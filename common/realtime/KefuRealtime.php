<?php

/**
 * 客服实时消息通道。
 *
 * 统一生成后台客服与会员的WebSocket JWT，并在消息事务提交后通过Channel发布轻量事件。
 */

namespace common\realtime;

use common\models\kefu\KefuMessage;
use common\models\member\Member;
use Channel\Client;
use mylibs\yii\config\Configure;
use Throwable;
use Yii;

/** 客服 WebSocket 连接配置和 Workerman Channel 发布入口。 */
final class KefuRealtime
{
    /** 后台所有客服共同订阅的频道。 */
    private const ADMIN_CHANNEL = 'kefu/admin';

    /** 会员个人频道前缀。 */
    private const MEMBER_CHANNEL = 'kefu/member';

    /** 客服消息事件名称。 */
    public const EVENT_MESSAGE = 'kefu.message';

    /** 会员读取客服消息的事件名称。 */
    public const EVENT_READ = 'kefu.read';

    /** 客服接待（领取/转交）变更事件名称。 */
    public const EVENT_AGENT = 'kefu.agent';

    /**
     * 生成当前后台客服的短期 WebSocket 连接配置。
     *
     * @param int $userId 后台客服ID
     * @param string $userName 后台客服名称
     * @return array{server: string, token: string}
     */
    public static function createAdminConnection(int $userId, string $userName): array
    {
        return self::createConnection('A', $userId, $userName, [self::ADMIN_CHANNEL]);
    }

    /**
     * 生成指定会员的短期 WebSocket 连接配置。
     *
     * @param int $memberId 会员ID
     * @param string $memberName 会员显示名称
     * @param int $authVersion 会员鉴权版本
     * @return array{server: string, token: string}
     */
    public static function createMemberConnection(int $memberId, string $memberName, int $authVersion): array
    {
        return self::createConnection(
            'M',
            $memberId,
            $memberName,
            [self::MEMBER_CHANNEL . '/' . $memberId],
            $authVersion
        );
    }

    /**
     * 校验H5会员访问Token并返回仍处于正常状态的会员。
     *
     * @return Member|null Token无效、过期、版本失效或会员冻结时返回null
     */
    public static function verifyMemberToken(string $token): ?Member
    {
        $payload = KefuTokenAdapter::decode($token);
        if (
            $payload === null
            || ($payload['platform'] ?? '') !== 'M'
        ) {
            return null;
        }
        $member = Member::findById((int) ($payload['id'] ?? 0));
        if (
            $member === null
            || (int) $member->status !== Member::STATUS_ACTIVE
            || (int) $member->auth_version !== (int) ($payload['authVersion'] ?? 0)
        ) {
            return null;
        }
        return $member;
    }

    /**
     * 通过Workerman Channel向客服Worker发布消息刷新事件。
     *
     * 消息已在数据库事务中提交，Channel临时不可用时只记录告警，不能把已发送消息改判为失败。
     */
    public static function publishMessage(KefuMessage $message, int $memberId): void
    {
        $config = (new Configure())->loadCommonConfig('kefu-realtime');
        $event = [
            'type' => self::EVENT_MESSAGE,
            'conversationId' => (int) $message->conversation_id,
            'messageId' => (int) $message->id,
            'memberId' => $memberId,
            'senderType' => (int) $message->sender_type,
            'messagePreview' => $message->getPreview(),
            'messageTime' => $message->create_time,
        ];
        try {
            Client::connect((string) $config['channelHost'], (int) $config['channelPort']);
            Client::publish('kefu_message', $event);
        } catch (Throwable $exception) {
            Yii::warning(
                '客服 Channel 实时消息发布失败：' . $exception->getMessage(),
                __METHOD__
            );
        }
    }

    /**
     * 向当前接待客服发布会员已读回执，避免客服工作台依赖人工刷新读取状态。
     *
     * @param int $conversationId 客服会话ID
     * @param int $memberId 读取消息的会员ID
     * @param int $userId 接待客服ID
     * @param int $lastReadMessageId 本次读取的最大消息ID
     */
    public static function publishMemberRead(
        int $conversationId,
        int $memberId,
        int $userId,
        int $lastReadMessageId
    ): void {
        $config = (new Configure())->loadCommonConfig('kefu-realtime');
        $event = [
            'type' => self::EVENT_READ,
            'conversationId' => $conversationId,
            'memberId' => $memberId,
            'targetUserId' => $userId,
            'lastReadMessageId' => $lastReadMessageId,
        ];
        try {
            Client::connect((string) $config['channelHost'], (int) $config['channelPort']);
            Client::publish('kefu_message', $event);
        } catch (Throwable $exception) {
            Yii::warning(
                '客服 Channel 已读回执发布失败：' . $exception->getMessage(),
                __METHOD__
            );
        }
    }

    /**
     * 向会员推送当前接待客服信息（领取/转交/结束时触发）。
     *
     * @param int $conversationId 客服会话ID
     * @param int $memberId 会话所属会员ID
     * @param int|null $userId 接待客服ID，null 表示暂无客服接待
     * @param string $userName 接待客服名称
     * @param int $status 会话状态
     */
    public static function publishAgentAssignment(
        int $conversationId,
        int $memberId,
        ?int $userId,
        string $userName,
        int $status
    ): void {
        $config = (new Configure())->loadCommonConfig('kefu-realtime');
        $event = [
            'type' => self::EVENT_AGENT,
            'conversationId' => $conversationId,
            'memberId' => $memberId,
            'agent' => $userId !== null ? [
                'id' => $userId,
                'name' => $userName,
            ] : null,
            'status' => $status,
        ];
        try {
            Client::connect((string) $config['channelHost'], (int) $config['channelPort']);
            Client::publish('kefu_message', $event);
        } catch (Throwable $exception) {
            Yii::warning(
                '客服 Channel 接待变更发布失败：' . $exception->getMessage(),
                __METHOD__
            );
        }
    }

    /**
     * 按消息中心既有协议签发只包含指定频道的连接 Token。
     *
     * @param string $platform 客户端平台标识
     * @param int $identityId 登录主体ID
     * @param string $identityName 登录主体名称
     * @param array<int, string> $channels 允许订阅的频道
     * @param int|null $authVersion 会员鉴权版本，后台客服连接不传
     * @return array{server: string, token: string}
     */
    private static function createConnection(
        string $platform,
        int $identityId,
        string $identityName,
        array $channels,
        ?int $authVersion = null
    ): array {
        $config = (new Configure())->loadCommonConfig('kefu-realtime');
        $payload = [
            'platform' => $platform,
            'id' => $identityId,
            'name' => $identityName,
            'channels' => $channels,
        ];
        if ($authVersion !== null) {
            $payload['authVersion'] = $authVersion;
        }

        return [
            'server' => (string) $config['server'],
            'token' => KefuTokenAdapter::issue($payload),
        ];
    }
}
