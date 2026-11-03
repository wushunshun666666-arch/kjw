<?php

/** 仅在测试事务内补充新彩种，结束后随测试回滚。 */
final class MarksixGames
{
    public static function seed(): void
    {
        if (Yii::$app->db->getTransaction() === null) { throw new RuntimeException('Test transaction required'); }
        foreach (['hk6', 'macau_new', 'macau_old'] as $code) {
            if (\common\models\lottery\Game::findOne(['code' => $code]) !== null) { continue; }
            $definition = \common\models\lottery\FeedCatalog::get($code);
            Yii::$app->db->createCommand()->insert('portal_game', ['code' => $code, 'name' => $definition['name'],
                'description' => '', 'ball_count' => 7, 'ball_min' => 1, 'ball_max' => 49, 'sort_order' => 80,
                'enabled' => 1, 'version' => 0, 'create_time' => '2026-11-03 00:00:00', 'update_time' => '2026-11-03 00:00:00'])->execute();
        }
    }
}
