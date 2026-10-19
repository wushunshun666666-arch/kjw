<?php

/**
 * 客服实时进程应用服务。
 *
 * 负责校验进程命令、配置独立运行文件并注册Workerman服务，协议处理仍由common层承担。
 */

namespace app\models\kefu;

use app\models\Service;
use Channel\Server as ChannelServer;
use common\realtime\KefuWebSocketServer;
use mylibs\yii\config\Configure;
use Workerman\Worker;
use Yii;

/** 客服WebSocket与Channel进程的Console用例编排服务。 */
final class RealtimeService extends Service
{
    /** Workerman允许的进程管理命令。 */
    private const COMMANDS = ['start', 'stop', 'restart', 'reload', 'status', 'connections'];

    /**
     * 注册并运行客服WebSocket进程。
     *
     * @param string $command 已从Console入口接收的Workerman命令
     * @param bool $daemon 是否以守护进程模式启动
     * @param bool $graceful 是否平滑停止或重载
     * @return bool 命令是否合法；合法命令会进入Workerman进程生命周期
     */
    public function runWebsocket(string $command, bool $daemon, bool $graceful): bool
    {
        if (!$this->validateCommand($command)) {
            return false;
        }

        $this->configureWorker('kefu-websocket', $command, $daemon, $graceful);
        $server = new KefuWebSocketServer();
        $server->register();
        Worker::runAll();

        return true;
    }

    /**
     * 注册并运行客服Channel跨进程通信服务。
     *
     * @param string $command 已从Console入口接收的Workerman命令
     * @param bool $daemon 是否以守护进程模式启动
     * @param bool $graceful 是否平滑停止或重载
     * @return bool 命令是否合法；合法命令会进入Workerman进程生命周期
     */
    public function runChannel(string $command, bool $daemon, bool $graceful): bool
    {
        if (!$this->validateCommand($command)) {
            return false;
        }

        $this->configureWorker('kefu-channel', $command, $daemon, $graceful);
        $config = (new Configure())->loadCommonConfig('kefu-realtime');
        new ChannelServer((string) $config['channelListenHost'], (int) $config['channelPort']);
        Worker::runAll();

        return true;
    }

    /**
     * 验证命令属于Workerman公开支持的管理动作。
     */
    private function validateCommand(string $command): bool
    {
        if (!in_array($command, self::COMMANDS, true)) {
            return $this->setError('不支持的Workerman进程命令。');
        }

        return true;
    }

    /**
     * 为不同实时服务设置互不冲突的PID、状态和日志文件。
     *
     * @param string $processName 仅由本类固定传入的进程名称
     * @param string $command 已通过白名单验证的进程命令
     * @param bool $daemon 是否以守护进程模式启动
     * @param bool $graceful 是否平滑停止或重载
     */
    private function configureWorker(string $processName, string $command, bool $daemon, bool $graceful): void
    {
        $runtimePath = Yii::getAlias('@runtime');
        Worker::$pidFile = $runtimePath . DIRECTORY_SEPARATOR . $processName . '.pid';
        Worker::$statusFile = $runtimePath . DIRECTORY_SEPARATOR . $processName . '.status';
        Worker::$logFile = $runtimePath . DIRECTORY_SEPARATOR . $processName . '.log';
        Worker::$command = $command
            . ($daemon ? ' -d' : '')
            . ($graceful ? ' -g' : '');
    }
}
