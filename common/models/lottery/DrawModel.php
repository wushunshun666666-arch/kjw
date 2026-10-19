<?php

namespace common\models\lottery;

use common\models\Error;
use common\models\LogicModel;
use mylibs\yii\db\Transaction;

/** 验证并幂等接收开奖记录，已入库结果不允许静默覆盖。 */
final class DrawModel extends LogicModel
{
    /** 接收标准数据；相同记录重试成功，冲突结果返回错误。 */
    public static function receive(DrawInput $input): Draw|Error
    {
        if (!$input->validate()) {
            return new Error($input->getErrors());
        }
        $transaction = new Transaction();
        $game = Game::findBySql('SELECT * FROM portal_game WHERE id = :id FOR UPDATE', [':id' => (int) $input->game_id])->one();
        if ($game === null) {
            $transaction->rollback();
            return new Error('彩种不存在。');
        }
        $balls = array_map('intval', explode(',', $input->numbers));
        if (count($balls) !== (int) $game->ball_count || min($balls) < $game->ball_min || max($balls) > $game->ball_max) {
            $transaction->rollback();
            return new Error('号码数量或范围不符合该彩种配置。');
        }
        if (FeedCatalog::get($game->code) !== null && (!FeedCatalog::matches($game) || !FeedCatalog::accepts($game->code, $balls))) {
            $transaction->rollback();
            return new Error('号码不符合固定采集源规则；赛车类号码必须互不重复。');
        }
        $numbers = implode(',', $balls);
        $existing = Draw::findOne(['game_id' => $game->id, 'issue' => $input->issue]);
        if ($existing !== null) {
            $transaction->rollback();
            return $existing->numbers === $numbers && $existing->draw_time === $input->draw_time
                ? $existing : new Error('该期已存在不同结果，请核对数据源；系统不会覆盖历史开奖。');
        }
        $entity = new Draw($input->getSafeAttributes());
        $entity->numbers = $numbers;
        $entity->create_time = (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s');
        if (!$entity->save()) {
            $transaction->rollback();
            return new Error($entity->getErrors());
        }
        $audit = Audit::record('draw', (int) $entity->id, 'receive');
        if (!$audit->save()) {
            $transaction->rollback();
            return new Error($audit->getErrors());
        }
        $transaction->commit();
        return $entity;
    }
}
