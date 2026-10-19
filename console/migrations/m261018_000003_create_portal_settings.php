<?php

use yii\db\Migration;

/** 把现有外观迁入后台管理，保留已有网站显示。 */
final class m261018_000003_create_portal_settings extends Migration
{
    /** 创建唯一配置与管理权限，不修改现有开奖数据。 */
    public function safeUp(): void
    {
        $this->createTable('portal_settings', [
            'id' => $this->primaryKey(), 'website' => $this->string(128)->notNull(), 'title' => $this->string(128)->notNull(),
            'landing' => $this->string(2048)->notNull(), 'ad_wx' => $this->string(2048)->notNull()->defaultValue(''),
            'ad_ww' => $this->string(2048)->notNull()->defaultValue(''), 'banner' => $this->string(128)->notNull(),
            'icon' => $this->string(128)->notNull(), 'ad_icon' => $this->string(128)->notNull()->defaultValue(''),
            'show_qrcode' => $this->smallInteger()->notNull()->defaultValue(1), 'notice_scroll' => $this->text()->notNull(),
            'ads_json' => $this->text()->notNull(), 'version' => $this->integer()->notNull()->defaultValue(0),
            'create_time' => $this->dateTime()->notNull(), 'update_time' => $this->dateTime()->notNull(),
        ]);
        $defaults = require dirname(__DIR__, 2) . '/frontend/config/portal.php';
        $ads = array_map(static fn (array $ad): array => $ad + ['enabled' => 1], $defaults['ad']);
        unset($defaults['ad'], $defaults['reference_predictions'], $defaults['reference_notices']);
        if (str_starts_with($defaults['ad_wx'], '//')) { $defaults['ad_wx'] = 'https:' . $defaults['ad_wx']; }
        $this->insert('portal_settings', $defaults + ['id' => 1, 'ads_json' => json_encode($ads, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'version' => 0, 'create_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')]);
        $module = (new \yii\db\Query())->from('module')->where(['url' => 'portal/draw/index'])->one($this->db);
        if (!$module) { return; }
        foreach (['index' => '站点与广告配置', 'check' => '配置字段校验', 'upload' => '上传门户图片', 'image' => '预览门户图片'] as $action => $name) {
            $this->insert('module', ['category_id' => $module['category_id'], 'name' => $name,
                'url' => 'portal/settings/' . $action, 'description' => '登录链接、广告和站点图片管理',
                'create_time' => date('Y-m-d H:i:s'), 'update_time' => date('Y-m-d H:i:s')]);
        }
    }

    /** 避免自动回滚删除管理员配置和权限。 */
    public function safeDown(): bool { return false; }
}
