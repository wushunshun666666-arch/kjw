<?php

/**
 * 客服 Workerman WebSocket 服务。
 *
 * 监听8600端口，连接关系只保存在当前Worker内存；跨进程消息通过2206 Channel进入。
 */

namespace common\realtime;

use Channel\Client;
use common\models\kefu\KefuModel;
use mylibs\yii\config\Configure;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Timer;
use Workerman\Worker;

/** 客服 WebSocket 服务注册器。 */
final class KefuWebSocketServer
{
    /** @var array<int, array{platform: string, id: int}> 已认证连接身份 */
    private array $connectionIdentities = [];

    /** @var array<int, int> 连接最近活动时间 */
    private array $connectionLastActive = [];

    /** @var Worker 客服WebSocket工作进程 */
    private Worker $worker;

    /** 根据当前环境配置创建客服WebSocket Worker。 */
    public function __construct()
    {
        $config = (new Configure())->loadCommonConfig('kefu-realtime');
        $listenAddress = 'websocket://' . $config['websocketListenHost'] . ':' . $config['websocketPort'];
        $this->worker = new Worker($listenAddress);
        $this->worker->name = 'kefu-websocket';
        $this->worker->count = 1;
    }

    /** 注册全部事件回调，由统一启动脚本执行Workerman事件循环。 */
    public function register(): void
    {
        $config = (new Configure())->loadCommonConfig('kefu-realtime');
        $this->worker->onWebSocketConnect = function (TcpConnection $connection, Request $request) use ($config): void {
            $identityResult = $this->resolveIdentity((string) $request->get('token', ''));
            if ($identityResult['identity'] === null) {
                $this->rejectHandshake($connection, $identityResult['reason'], (string) $config['keyId']);
                return;
            }
            $identity = $identityResult['identity'];
            $this->connectionIdentities[$connection->id] = $identity;
            $this->connectionLastActive[$connection->id] = time();
            if ($identity['platform'] === 'A') {
                $connection->send($this->createPresencePayload());
            } else {
                $this->broadcastPresence();
            }
        };
        $this->worker->onMessage = function (TcpConnection $connection, string $rawMessage): void {
            $this->connectionLastActive[$connection->id] = time();
            $message = json_decode($rawMessage, true);
            if (!is_array($message)) {
                return;
            }
            if (($message['type'] ?? '') === 'ping') {
                $connection->send('{"type":"pong"}');
                return;
            }
            if (($message['type'] ?? '') === 'delivery_ack') {
                $identity = $this->connectionIdentities[$connection->id] ?? null;
                if ($identity !== null) {
                    KefuModel::acknowledgeDelivery(
                        (int) ($message['messageId'] ?? 0),
                        $identity['platform'],
                        $identity['id']
                    );
                }
            }
        };
        $this->worker->onClose = function (TcpConnection $connection): void {
            $identity = $this->connectionIdentities[$connection->id] ?? null;
            unset($this->connectionIdentities[$connection->id], $this->connectionLastActive[$connection->id]);
            if ($identity !== null && $identity['platform'] === 'M') {
                $this->broadcastPresence();
            }
        };
        $this->worker->onWorkerStart = function () use ($config): void {
            Client::connect((string) $config['channelHost'], (int) $config['channelPort']);
            Client::on('kefu_message', fn (array $event) => $this->broadcast($event));
            Timer::add(30, function (): void {
                $now = time();
                foreach ($this->worker->connections as $connection) {
                    if (($this->connectionLastActive[$connection->id] ?? $now) < $now - 90) {
                        $connection->close();
                    }
                }
            });
        };
    }

    /** 按客服或目标会员身份在当前Worker内存中直接推送。 */
    private function broadcast(array $event): void
    {
        $payload = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            return;
        }
        foreach ($this->worker->connections as $connection) {
            $identity = $this->connectionIdentities[$connection->id] ?? null;
            if ($identity === null) {
                continue;
            }
            $targetUserId = (int) ($event['targetUserId'] ?? 0);
            $isAdmin = $identity['platform'] === 'A'
                && ($targetUserId === 0 || $identity['id'] === $targetUserId);
            $isMember = $identity['platform'] === 'M' && $identity['id'] === (int) ($event['memberId'] ?? 0);
            if ($isAdmin || $isMember) {
                $connection->send($payload);
            }
        }
    }

    /** 向全部在线客服推送去重后的会员在线快照。 */
    private function broadcastPresence(): void
    {
        $payload = $this->createPresencePayload();
        foreach ($this->worker->connections as $connection) {
            if (($this->connectionIdentities[$connection->id]['platform'] ?? '') === 'A') {
                $connection->send($payload);
            }
        }
    }

    /** 生成会员在线人数和会员ID列表的安全JSON。 */
    private function createPresencePayload(): string
    {
        $memberIds = [];
        foreach ($this->connectionIdentities as $identity) {
            if ($identity['platform'] === 'M') {
                $memberIds[$identity['id']] = $identity['id'];
            }
        }
        $memberIds = array_values($memberIds);
        sort($memberIds);
        $payload = json_encode([
            'type' => 'kefu.presence',
            'onlineCount' => count($memberIds),
            'memberIds' => $memberIds,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($payload) ? $payload : '{"type":"kefu.presence","onlineCount":0,"memberIds":[]}';
    }

    /**
     * 通过统一Token适配器提取服务端可信身份，并返回不可公开的拒绝原因。
     *
     * @return array{identity: array{platform: string, id: int}|null, reason: string}
     */
    private function resolveIdentity(string $token): array
    {
        if ($token === '') {
            return ['identity' => null, 'reason' => 'missing_token'];
        }
        $payload = KefuTokenAdapter::decode($token);
        if ($payload === null) {
            return ['identity' => null, 'reason' => 'invalid_token'];
        }
        $platform = (string) ($payload['platform'] ?? '');
        $identityId = (int) ($payload['id'] ?? 0);
        if (
            !in_array($platform, ['A', 'M'], true)
            || $identityId < 1
        ) {
            return ['identity' => null, 'reason' => 'invalid_identity'];
        }
        if ($platform === 'M' && KefuRealtime::verifyMemberToken($token) === null) {
            return ['identity' => null, 'reason' => 'member_unavailable'];
        }
        return [
            'identity' => ['platform' => $platform, 'id' => $identityId],
            'reason' => '',
        ];
    }

    /**
     * 在升级协议前返回可诊断的鉴权拒绝响应，日志中不保存Token和身份资料。
     *
     * @param string $reason 仅供运维识别的固定拒绝原因
     * @param string $keyId 当前Worker使用的非敏感密钥标识
     */
    private function rejectHandshake(TcpConnection $connection, string $reason, string $keyId): void
    {
        Worker::log('客服 WebSocket 握手被拒绝：reason=' . $reason . '，keyId=' . $keyId);
        $connection->close(
            "HTTP/1.1 401 Unauthorized\r\nConnection: close\r\nContent-Length: 0\r\n\r\n",
            true
        );
    }

}
