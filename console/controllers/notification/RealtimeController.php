<?php

namespace app\controllers\notification;

use app\controllers\Controller;
use app\models\notification\RealtimeService;

/** 后台站内消息实时提醒进程Console入口。 */
final class RealtimeController extends Controller
{
    /**
     * 管理8700端口的后台站内消息WebSocket进程。
     *
     * @param string $command Workerman命令：start、stop、restart、reload、status或connections
     * @param int $daemon 是否以守护进程模式启动，1为是
     * @param int $graceful 是否平滑停止或重载，1为是
     * @return int Console退出码
     */
    public function actionWebsocket(string $command = 'start', int $daemon = 0, int $graceful = 0): int
    {
        $this->log('开始执行后台站内消息WebSocket进程命令：' . $command);
        $service = new RealtimeService();
        if (!$service->runWebsocket($command, $daemon === 1, $graceful === 1)) {
            return $this->error($service->getError());
        }
        return 0;
    }
}
