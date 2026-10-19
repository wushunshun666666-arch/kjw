<?php

namespace app\models\notification;

use app\models\Service;
use common\realtime\NoticeWebSocketServer;
use Workerman\Worker;
use Yii;

/** 后台站内消息实时提醒进程应用服务。 */
final class RealtimeService extends Service
{
    /** Workerman允许的进程管理命令。 */
    private const COMMANDS = ['start', 'stop', 'restart', 'reload', 'status', 'connections'];

    /**
     * 注册并运行后台站内消息WebSocket进程。
     *
     * @param string $command 已从Console入口接收的Workerman命令
     * @param bool $daemon 是否以守护进程模式启动
     * @param bool $graceful 是否平滑停止或重载
     */
    public function runWebsocket(string $command, bool $daemon, bool $graceful): bool
    {
        if (!in_array($command, self::COMMANDS, true)) {
            return $this->setError('不支持的Workerman进程命令。');
        }
        $runtimePath = Yii::getAlias('@runtime');
        Worker::$pidFile = $runtimePath . DIRECTORY_SEPARATOR . 'admin-message-websocket.pid';
        Worker::$statusFile = $runtimePath . DIRECTORY_SEPARATOR . 'admin-message-websocket.status';
        Worker::$logFile = $runtimePath . DIRECTORY_SEPARATOR . 'admin-message-websocket.log';
        Worker::$command = $command
            . ($daemon ? ' -d' : '')
            . ($graceful ? ' -g' : '');
        $server = new NoticeWebSocketServer();
        $server->register();
        Worker::runAll();
        return true;
    }
}
