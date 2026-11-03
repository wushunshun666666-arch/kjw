<?php

namespace app\models\portal;

/** 命令行采集用例，将错误转换为 CLI 可读取状态。 */
class CollectService extends \app\models\Service
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
        if (\common\models\lottery\SharedDataConfig::current()->isClient()) {
            return $this->setError('当前为共享数据分站，请停止此站采集任务；开奖由中心统一采集。');
        }
        $failed = false;
        $consecutiveFailures = 0;
        for ($round = 0; (int) $form->rounds === 0 || $round < (int) $form->rounds; $round++) {
            $this->clearErrors();
            $result = false;
            $error = '';
            try {
                $result = $this->collect($form->game);
                $error = $result === false ? (string) $this->getError() : implode('；', $result['errors']);
            } catch (\Throwable $exception) {
                $error = '采集运行异常（' . get_class($exception) . '），连接将重建并退避重试。';
                \Yii::error(['type' => get_class($exception), 'file' => $exception->getFile(),
                    'line' => $exception->getLine()], 'portal.collection.exception');
            } finally {
                $this->closeConnection();
            }
            $failed = $failed || $error !== '';
            // 部分来源故障仍保持正常调度，避免健康彩种被全局退避拖慢。
            $healthySources = is_array($result)
                ? array_diff(array_keys($result['results']), array_keys($result['errors'])) : [];
            $consecutiveFailures = $error === '' || $healthySources !== [] ? 0 : $consecutiveFailures + 1;
            $report($result, $error);
            if ((int) $form->rounds === 0 || $round + 1 < (int) $form->rounds) {
                $this->pause((int) min(300, (int) $form->interval * (2 ** min($consecutiveFailures, 6))));
            }
        }
        return $failed ? $this->setError('部分来源采集失败，详情见本轮输出和后台采集状态。') : true;
    }

    /** 每轮释放连接，下一轮由 Yii 按需重连。 */
    protected function closeConnection(): void { \Yii::$app->db->close(); }

    /** 有界退避；测试可替换等待以验证恢复流程。 */
    protected function pause(int $seconds): void { sleep($seconds); }
}
