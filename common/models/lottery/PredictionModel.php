<?php

namespace common\models\lottery;

use common\models\Error;
use mylibs\yii\db\Transaction;

/** 采集完成后生成下一期预测，锁定彩种以隔离并发入库。 */
final class PredictionModel extends \common\models\LogicModel
{
    /** 无足够样本、过期或已有结果时返回 null；已有预测原样返回。
     * @return Prediction|Error|null 本地事前预测或业务错误
     */
    public static function generateNext(int $gameId): Prediction|Error|null
    {
        $config = SharedDataConfig::current();
        if ($config->isClient()) {
            return new Error('共享数据分站使用中心预测，不在本地生成预测。');
        }
        $transaction = new Transaction();
        try {
            $game = Game::findBySql('SELECT * FROM portal_game WHERE id = :id FOR UPDATE', [':id' => $gameId])->one();
            if ($game === null || $game->code !== 'jnd28' || (!$config->isCenter() && (int) $game->enabled !== Game::VISIBLE)
                || (int) $game->ball_count !== 3 || (int) $game->ball_min !== 0 || (int) $game->ball_max !== 9) {
                $transaction->commit(); return null;
            }
            $history = Draw::find()->select(['issue', 'numbers', 'draw_time'])->where(['game_id' => $gameId])
                ->orderBy(['draw_time' => SORT_DESC, 'id' => SORT_DESC])->limit(PredictionAlgorithm::WINDOW)->asArray()->all();
            $latest = $history[0] ?? null;
            if ($latest === null || !ctype_digit($latest['issue']) || strlen($latest['issue']) > 13) {
                $transaction->commit(); return null;
            }
            $issue = (string) ((int) $latest['issue'] + 1);
            $existing = Prediction::findOne(['game_id' => $gameId, 'issue' => $issue]);
            if ($existing !== null) { $transaction->commit(); return $existing; }
            $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai'));
            $sourceTime = new \DateTimeImmutable($latest['draw_time'], new \DateTimeZone('Asia/Shanghai'));
            $targetTime = $sourceTime->modify('+' . PredictionAlgorithm::INTERVAL_SECONDS . ' seconds');
            // 只生成尚未开奖且距离预定开奖至少 5 秒的下一期；历史导入不补造预测。
            if ($sourceTime > $now || $targetTime->getTimestamp() - $now->getTimestamp() <= 5
                || Draw::find()->where(['game_id' => $gameId, 'issue' => $issue])->exists()) {
                $transaction->commit(); return null;
            }
            $values = (new PredictionAlgorithm())->calculate($history);
            if ($values === null) { $transaction->commit(); return null; }
            $entity = new Prediction($values + ['game_id' => $gameId, 'issue' => $issue, 'source_issue' => $latest['issue'],
                'algorithm' => PredictionAlgorithm::VERSION, 'basis_hash' => hash('sha256', $values['basis_json']),
                'create_time' => $now->format('Y-m-d H:i:s'), 'target_time' => $targetTime->format('Y-m-d H:i:s')]);
            if (!$entity->save()) { $transaction->rollback(); return new Error($entity->getErrors()); }
            $audit = Audit::record('prediction', (int) $entity->id, 'generate');
            if (!$audit->save()) { $transaction->rollback(); return new Error($audit->getErrors()); }
            $transaction->commit();
            return $entity;
        } catch (\Throwable $error) {
            $transaction->rollback();
            throw $error;
        }
    }
}
