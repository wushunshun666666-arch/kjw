<?php

namespace common\models\lottery;

use common\models\Error;
use mylibs\yii\db\Transaction;

/** 按彩种隔离采集、入库与状态；网络读取不占用数据库事务。 */
final class CollectionModel extends \common\models\LogicModel
{
    private FeedGateway $gateway;

    /** 注入来源适配器，以便验收上游故障和重复采集。 */
    public function __construct(?FeedGateway $gateway = null)
    {
        $this->gateway = $gateway ?? new FeedGateway();
        parent::__construct();
    }

    /** 返回本轮接收统计；失败保留已成功入库记录，可安全重试。
     * @return array{received: int, inserted: int}|false
     */
    public function collect(string $code = 'jnd28'): array|false
    {
        $this->clearErrors();
        if (FeedCatalog::get($code) === null) { return $this->setError('未配置该彩种的采集地址。'); }
        $mutex = new \yii\mutex\FileMutex(['mutexPath' => '@common/runtime/portal-collection-locks']);
        $lock = 'portal-collect-' . $code;
        if (!$mutex->acquire($lock, 0)) { return $this->setError('该彩种已有采集任务运行，本次跳过。'); }
        try {
            $game = Game::findOne(['code' => $code]);
            if ($game === null) { return $this->setError('请先执行彩种数据库迁移。'); }
            if (!FeedCatalog::matches($game)) { return $this->fail($game, '彩种配置与采集源号码规则不一致。'); }
            $rows = $this->gateway->fetch((int) $game->id, $code);
            if (Error::isError($rows)) { return $this->fail($game, (string) $rows->getError()); }
            $inserted = 0;
            foreach ($rows as $row) {
                $exists = Draw::find()->where(['game_id' => $game->id, 'issue' => $row->issue])->exists();
                $result = DrawModel::receive($row);
                if (Error::isError($result)) { return $this->fail($game, (string) $result->getError(), count($rows), $inserted); }
                $inserted += $exists ? 0 : 1;
            }
            $prediction = PredictionModel::generateNext((int) $game->id);
            if (Error::isError($prediction)) { return $this->fail($game, (string) $prediction->getError(), count($rows), $inserted); }
            $this->recordState($game, '', count($rows), $inserted);
            return ['received' => count($rows), 'inserted' => $inserted];
        } finally {
            $mutex->release($lock);
        }
    }

    /** 各来源独立运行，一个接口失败不阻止其他彩种更新。 */
    public function collectAll(): array
    {
        $summary = ['received' => 0, 'inserted' => 0, 'results' => [], 'errors' => []];
        foreach (array_keys(FeedCatalog::GAMES) as $code) {
            $result = $this->collect($code);
            if ($result === false) { $summary['errors'][$code] = $this->getError(); continue; }
            $summary['results'][$code] = $result;
            $summary['received'] += $result['received'];
            $summary['inserted'] += $result['inserted'];
        }
        return $summary;
    }

    /** 保存失败状态但不覆盖上一次成功时间。 */
    private function fail(Game $game, string $message, int $received = 0, int $inserted = 0): false
    {
        $this->recordState($game, $message, $received, $inserted);
        return $this->setError($message);
    }

    /** 在独立短事务中保存本彩种状态快照，调用方持有共享采集锁。 */
    private function recordState(Game $game, string $error, int $received, int $inserted): void
    {
        $transaction = new Transaction();
        $state = CollectionState::findOne((int) $game->id) ?? new CollectionState(['game_id' => $game->id]);
        $state->attempt_time = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s');
        if ($error === '') { $state->success_time = $state->attempt_time; }
        $state->error = mb_substr($error, 0, 255);
        $state->received = $received;
        $state->inserted = $inserted;
        if (!$state->save()) { throw new \RuntimeException('无法保存采集状态：' . json_encode($state->getErrors(), JSON_UNESCAPED_UNICODE)); }
        $transaction->commit();
    }
}
