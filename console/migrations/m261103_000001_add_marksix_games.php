<?php

use yii\db\Migration;

/** 只新增六合彩彩种，不修改既有配置和任何历史开奖。 */
final class m261103_000001_add_marksix_games extends Migration
{
    public function safeUp(): void
    {
        foreach (['hk6' => '香港六合彩', 'macau_new' => '新澳门六合彩', 'macau_old' => '老澳门六合彩'] as $code => $name) {
            if ((new \yii\db\Query())->from('portal_game')->where(['code' => $code])->exists($this->db)) { continue; }
            $this->insert('portal_game', ['code' => $code, 'name' => $name,
                'description' => '6个正码及1个特码；来源：69.165.75.20:86采集接口',
                'ball_count' => 7, 'ball_min' => 1, 'ball_max' => 49, 'sort_order' => match ($code) {
                    'hk6' => 70, 'macau_new' => 80, default => 90 }, 'enabled' => 1, 'version' => 0,
                'create_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')]);
        }
    }

    public function safeDown(): bool { return false; }
}
