<?php

/**
 * 客服实时服务Console入口。
 *
 * 接收Workerman进程命令并交由同域Service启动客服WebSocket或Channel服务。
 */

namespace app\controllers\kefu;

use app\controllers\Controller;
use app\models\kefu\RealtimeService;

/** 客服实时进程的命令行控制器。 */
final class RealtimeController extends Controller
{
    /**
     * 管理客服WebSocket进程。
     *
     * @param string $command Workerman命令：start、stop、restart、reload、status或connections
     * @param int $daemon 是否以守护进程模式启动，1为是
     * @param int $graceful 是否平滑停止或重载，1为是
     * @return int Console退出码
     */
    public function actionWebsocket(string $command = 'start', int $daemon = 0, int $graceful = 0): int
    {
        $this->log('开始执行客服WebSocket进程命令：' . $command);
        $service = new RealtimeService();
        if (!$service->runWebsocket($command, $daemon === 1, $graceful === 1)) {
            return $this->error($service->getError());
        }

        return 0;
    }

    /**
     * 管理客服Channel跨进程通信服务。
     *
     * @param string $command Workerman命令：start、stop、restart、reload、status或connections
     * @param int $daemon 是否以守护进程模式启动，1为是
     * @param int $graceful 是否平滑停止或重载，1为是
     * @return int Console退出码
     */
    public function actionChannel(string $command = 'start', int $daemon = 0, int $graceful = 0): int
    {
        $this->log('开始执行客服Channel进程命令：' . $command);
        $service = new RealtimeService();
        if (!$service->runChannel($command, $daemon === 1, $graceful === 1)) {
            return $this->error($service->getError());
        }

        return 0;
    }
}
