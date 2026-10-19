<?php

namespace app\models\portal;

/** 命令行采集用例，将错误转换为 CLI 可读取状态。 */
final class CollectService extends \app\models\Service
{
    /** 通过共享领域动作接收指定彩种或所有固定来源。 */
    public function collect(string $game = 'all'): array|false
    {
        $form = new CollectForm(['game' => $game]);
        if (!$form->validate()) { return $this->addErrors($form->getErrors()); }
        $model = new \common\models\lottery\CollectionModel();
        if ($game === 'all') { return $model->collectAll(); }
        $result = $model->collect($game);
        return $result === false ? $this->addErrors($model->getErrors())
            : $result + ['results' => [$game => $result], 'errors' => []];
    }

    /** 常驻轮询在用例层编排；每轮关闭连接，故障不会停止后续轮次。 */
    public function watch(CollectForm $form, callable $report): bool
    {
        if (!$form->validate()) { return $this->addErrors($form->getErrors()); }
        $failed = false;
        for ($round = 0; (int) $form->rounds === 0 || $round < (int) $form->rounds; $round++) {
            $service = new self();
            $result = $service->collect($form->game);
            $error = $result === false ? (string) $service->getError() : implode('；', $result['errors']);
            $failed = $failed || $error !== '';
            $report($result, $error);
            \Yii::$app->db->close();
            if ((int) $form->rounds === 0 || $round + 1 < (int) $form->rounds) { sleep((int) $form->interval); }
        }
        return $failed ? $this->setError('部分来源采集失败，详情见本轮输出和后台采集状态。') : true;
    }
}
