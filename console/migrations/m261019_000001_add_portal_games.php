<?php

use yii\db\Migration;

/** 接入六个新增彩种并持久化各来源最近采集状态。 */
final class m261019_000001_add_portal_games extends Migration
{
    /** 保留现有彩种配置与历史，只补充缺少的来源。 */
    public function safeUp(): void
    {
        $games = [
            ['az51', '澳洲幸运5', 5, 0, 9], ['xyft_168', '幸运飞艇', 10, 1, 10],
            ['jspk10', '急速赛车', 10, 1, 10], ['jsssc', '急速时时彩', 5, 0, 9],
            ['jsft_168', '急速飞艇', 10, 1, 10], ['az10', '澳洲幸运10', 10, 1, 10],
        ];
        foreach ($games as $index => [$code, $name, $count, $min, $max]) {
            if ((new \yii\db\Query())->from('portal_game')->where(['code' => $code])->exists($this->db)) { continue; }
            $this->insert('portal_game', [
                'code' => $code, 'name' => $name, 'ball_count' => $count, 'ball_min' => $min, 'ball_max' => $max,
                'description' => $count === 5 ? '五位号码 0–9，保留开奖顺序' : '第1至第10名，号码 1–10 不重复',
                'sort_order' => ($index + 1) * 10, 'enabled' => 1, 'version' => 0,
                'create_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s'),
            ]);
        }
        $this->createTable('portal_collection_state', [
            'game_id' => $this->integer()->notNull(),
            'attempt_time' => $this->dateTime()->notNull(),
            'success_time' => $this->dateTime()->null(),
            'error' => $this->string(255)->notNull()->defaultValue(''),
            'received' => $this->integer()->notNull()->defaultValue(0),
            'inserted' => $this->integer()->notNull()->defaultValue(0),
            'PRIMARY KEY ([[game_id]])',
        ]);
        $this->addForeignKey('fk_portal_collection_game', 'portal_collection_state', 'game_id', 'portal_game', 'id', 'RESTRICT', 'RESTRICT');
    }

    /** 防止回滚删除已采集的真实记录及状态。 */
    public function safeDown(): bool { return false; }
}
