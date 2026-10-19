<?php

namespace common\realtime;

use common\models\system\User;
use mylibs\yii\config\Configure;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Redis\Client as RedisClient;
use Workerman\Timer;
use Workerman\Worker;

/**
 * 后台站内消息Workerman WebSocket服务。
 *
 * 本服务仅向后台运营用户推送刷新提醒，不承载客服消息或客户端连接。
 */
final class NoticeWebSocketServer
{
    /** @var array<int, int> 已认证连接对应的后台用户ID */
    private array $connectionUserIds = [];

    /** @var array<int, int> 连接最近活动时间 */
    private array $connectionLastActive = [];

    /** @var Worker 后台站内消息WebSocket进程 */
    private Worker $worker;

    /** 根据当前环境配置创建8700端口的后台通知Worker。 */
    public function __construct()
    {
        $config = (new Configure())->loadCommonConfig('message-center');
        $address = 'websocket://' . $config['websocketListenHost'] . ':' . $config['websocketPort'];
        $this->worker = new Worker($address);
        $this->worker->name = 'admin-message-websocket';
        $this->worker->count = 1;
    }

    /** 注册鉴权、心跳、Redis订阅和定向推送回调。 */
    public function register(): void
    {
        $config = (new Configure())->loadCommonConfig('message-center');
        $this->worker->onWebSocketConnect = function (TcpConnection $connection, Request $request): void {
            $payload = NoticeTokenAdapter::decode((string) $request->get('token', ''));
            $userId = (int) ($payload['id'] ?? 0);
            $user = $userId > 0 ? User::findOne(['id' => $userId, 'status' => User::STATUS_1000]) : null;
            if ($user === null) {
                Worker::log('后台站内消息WebSocket握手被拒绝：invalid_identity。');
                $connection->close("HTTP/1.1 401 Unauthorized\r\nConnection: close\r\nContent-Length: 0\r\n\r\n", true);
                return;
            }
            $this->connectionUserIds[$connection->id] = $userId;
            $this->connectionLastActive[$connection->id] = time();
            $connection->send('{"type":"welcome","message":"hello welcome"}');
        };
        $this->worker->onMessage = function (TcpConnection $connection, string $rawMessage): void {
            $this->connectionLastActive[$connection->id] = time();
            $message = json_decode($rawMessage, true);
            if (is_array($message) && ($message['type'] ?? '') === 'ping') {
                $connection->send('{"type":"pong"}');
            }
        };
        $this->worker->onClose = function (TcpConnection $connection): void {
            unset($this->connectionUserIds[$connection->id], $this->connectionLastActive[$connection->id]);
        };
        $this->worker->onWorkerStart = function () use ($config): void {
            $redis = new RedisClient('redis://' . $config['redisHost'] . ':' . $config['redisPort']);
            $redis->subscribe((string) $config['redisChannel'], function (string $channel, string $rawPayload): void {
                $this->broadcast($rawPayload);
            });
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

    /** 将Redis事件仅推送给事件声明的后台接收用户。 */
    private function broadcast(string $rawPayload): void
    {
        $event = json_decode($rawPayload, true);
        if (!is_array($event) || ($event['type'] ?? '') !== 'admin.message') {
            return;
        }
        $targetUserIds = array_map('intval', is_array($event['targetUserIds'] ?? null) ? $event['targetUserIds'] : []);
        foreach ($this->worker->connections as $connection) {
            $userId = $this->connectionUserIds[$connection->id] ?? 0;
            if ($userId > 0 && in_array($userId, $targetUserIds, true)) {
                $connection->send($rawPayload);
            }
        }
    }
}
