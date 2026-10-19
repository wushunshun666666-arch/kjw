<?php

use yii\db\Migration;

/** 创建开奖门户的数据表，不修改现有业务数据。 */
final class m261018_000001_create_portal_tables extends Migration
{
    /** 创建彩种、不可变开奖记录、公告及审计表。 */
    public function safeUp(): void
    {
        $this->createTable('portal_game', [
            'id' => $this->primaryKey(),
            'code' => $this->string(32)->notNull(),
            'name' => $this->string(64)->notNull(),
            'description' => $this->string(255)->notNull()->defaultValue(''),
            'ball_count' => $this->smallInteger()->notNull()->defaultValue(3),
            'ball_min' => $this->smallInteger()->notNull()->defaultValue(0),
            'ball_max' => $this->smallInteger()->notNull()->defaultValue(9),
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'enabled' => $this->smallInteger()->notNull()->defaultValue(0),
            'version' => $this->integer()->notNull()->defaultValue(0),
            'create_time' => $this->dateTime()->notNull(),
            'update_time' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('uq_portal_game_code', 'portal_game', 'code', true);
        $this->createIndex('ix_portal_game_visible', 'portal_game', ['enabled', 'sort_order']);
        $this->createTable('portal_draw', [
            'id' => $this->primaryKey(),
            'game_id' => $this->integer()->notNull(),
            'issue' => $this->string(64)->notNull(),
            'draw_time' => $this->dateTime()->notNull(),
            'numbers' => $this->string(255)->notNull(),
            'source' => $this->string(128)->notNull(),
            'create_time' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('uq_portal_draw_issue', 'portal_draw', ['game_id', 'issue'], true);
        $this->createIndex('ix_portal_draw_time', 'portal_draw', ['game_id', 'draw_time', 'id']);
        $this->addForeignKey('fk_portal_draw_game', 'portal_draw', 'game_id', 'portal_game', 'id', 'RESTRICT', 'RESTRICT');
        $this->createTable('portal_notice', [
            'id' => $this->primaryKey(),
            'title' => $this->string(128)->notNull(),
            'content' => $this->text()->notNull(),
            'enabled' => $this->smallInteger()->notNull()->defaultValue(0),
            'version' => $this->integer()->notNull()->defaultValue(0),
            'create_time' => $this->dateTime()->notNull(),
            'update_time' => $this->dateTime()->notNull(),
        ]);
        $this->createIndex('ix_portal_notice_visible', 'portal_notice', ['enabled', 'id']);
        $this->createTable('portal_audit', [
            'id' => $this->primaryKey(),
            'entity_type' => $this->string(32)->notNull(),
            'entity_id' => $this->integer()->notNull(),
            'action' => $this->string(32)->notNull(),
            'operator_id' => $this->integer()->notNull(),
            'create_time' => $this->dateTime()->notNull(),
        ]);
        $this->insert('portal_game', [
            'code' => 'jnd28', 'name' => '加拿大28', 'description' => '三位原始号码 · 和值 0–27',
            'ball_count' => 3, 'ball_min' => 0, 'ball_max' => 9, 'sort_order' => 0,
            'enabled' => 1, 'version' => 0,
            'create_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s'),
        ]);
        $this->registerPermissions();
    }

    /** 在已有模块体系注册独立门户路由，不继承其他开奖业务权限。 */
    private function registerPermissions(): void
    {
        $schema = $this->db->schema;
        if ($schema->getTableSchema('category') === null || $schema->getTableSchema('module') === null) {
            return;
        }
        $parent = (new \yii\db\Query())->from('category')->where(['type' => 1000, 'level' => 0])->one($this->db);
        if (!$parent) { return; }
        $this->insert('category', [
            'name' => '开奖门户', 'type' => 1000, 'parent_id' => $parent['id'], 'level' => 1,
            'orderby' => 0, 'status' => 0, 'create_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s'),
        ]);
        $categoryId = $this->db->getLastInsertID();
        foreach (['game' => '彩种', 'notice' => '公告', 'draw' => '开奖记录'] as $controller => $name) {
            $actions = $controller === 'draw' ? ['index', 'list', 'add', 'check-add']
                : ['index', 'list', 'add', 'modify', 'detail', 'check-add', 'check-modify'];
            foreach ($actions as $action) {
                $route = 'portal/' . $controller . '/' . $action;
                $this->insert('module', [
                    'category_id' => $categoryId, 'name' => $name . ' ' . $action, 'url' => $route,
                    'description' => '开奖门户独立管理权限',
                    'create_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s'),
                ]);
            }
        }
    }

    /** 回滚结构会删除开奖记录，仅在确认数据可丢弃时使用。 */
    public function safeDown(): void
    {
        echo "该迁移包含不可变开奖记录和权限注册，禁止自动回滚，请备份后人工处理。\n";
        throw new \yii\base\NotSupportedException('不可自动回滚生产开奖数据。');
    }
}
