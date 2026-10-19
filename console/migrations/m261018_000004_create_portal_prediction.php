<?php

use yii\db\Migration;

/** 新增本地事前预测和算法快照，不回填历史命中记录。 */
final class m261018_000004_create_portal_prediction extends Migration
{
    /** 保留每期唯一预测及其原始历史依据。 */
    public function safeUp(): void
    {
        $this->createTable('portal_prediction', [
            'id' => $this->primaryKey(), 'game_id' => $this->integer()->notNull(),
            'issue' => $this->string(64)->notNull(), 'source_issue' => $this->string(64)->notNull(),
            'algorithm' => $this->string(64)->notNull(), 'pdx' => $this->string(2)->notNull(), 'pds' => $this->string(2)->notNull(),
            'sample_count' => $this->integer()->notNull(), 'basis_json' => $this->text()->notNull(),
            'basis_hash' => $this->string(64)->notNull(), 'scores_json' => $this->text()->notNull(),
            'target_time' => $this->dateTime()->notNull(), 'create_time' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('uq_portal_prediction_issue', 'portal_prediction', ['game_id', 'issue'], true);
        $this->addForeignKey('fk_portal_prediction_game', 'portal_prediction', 'game_id', 'portal_game', 'id', 'RESTRICT', 'RESTRICT');
    }

    /** 已发布的预测依据不能自动删除。 */
    public function safeDown(): bool { return false; }
}
