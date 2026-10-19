<?php

use yii\db\Migration;

/** 注册受独立权限保护的手动采集入口。 */
final class m261018_000002_register_portal_collect_permission extends Migration
{
    /** 复用已建立的门户权限分类。 */
    public function safeUp(): void
    {
        if ($this->db->schema->getTableSchema('module') === null) { return; }
        $module = (new \yii\db\Query())->from('module')->where(['url' => 'portal/draw/index'])->one($this->db);
        if (!$module) { return; }
        $this->insert('module', [
            'category_id' => $module['category_id'], 'name' => '采集加拿大28', 'url' => 'portal/draw/collect',
            'description' => '从固定数据源接收最近开奖记录',
            'create_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s'),
        ]);
    }

    /** 权限迁移不自动撤销已分配的角色关联。 */
    public function safeDown(): bool { return false; }
}
