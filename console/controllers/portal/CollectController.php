<?php

namespace app\controllers\portal;

use app\models\portal\CollectService;
use app\models\portal\CollectForm;

/** 开奖采集命令，供服务器计划任务调用。 */
final class CollectController extends \app\controllers\Controller
{
    /** 单次采集；默认全部彩种，也可传入固定来源代码。 */
    public function actionIndex(string $game = 'all'): int
    {
        $this->log('开始采集：' . $game);
        $service = new CollectService();
        $result = $service->collect($game);
        if ($result === false) { return $this->error($service->getError()); }
        $this->report($result, implode('；', $result['errors']));
        return $result['errors'] === [] ? 0 : 1;
    }

    /** 常驻采集；interval 秒，rounds=0 持续运行，game 默认 all。 */
    public function actionWatch(int $interval = 5, int $rounds = 0, string $game = 'all'): int
    {
        $form = new CollectForm(['interval' => $interval, 'rounds' => $rounds, 'game' => $game]);
        $service = new CollectService();
        $this->stdout('持续采集启动：' . $game . '；按 Ctrl+C 停止。' . PHP_EOL);
        return $service->watch($form, $this->report(...)) ? 0 : $this->error($service->getError());
    }

    /** 输出逐来源统计，保留可识别的失败彩种和命令日志。 */
    private function report(array|false $result, string $error): void
    {
        $message = date('Y-m-d H:i:s') . ' ' . json_encode($result === false ? ['error' => $error] : $result, JSON_UNESCAPED_UNICODE);
        $this->stdout($message . PHP_EOL);
        $this->log($message);
    }
}
