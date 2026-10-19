<?php

namespace common\models\lottery;

/** 站点配置单例实体。
 * @property int $id
 * @property string $website
 * @property string $title
 * @property string $landing
 * @property string $ad_wx
 * @property string $ad_ww
 * @property string $banner
 * @property string $icon
 * @property string $ad_icon
 * @property int $show_qrcode
 * @property string $notice_scroll
 * @property string $ads_json
 * @property int $version
 */
final class Settings extends \common\models\ActiveRecord
{
    /** 使用门户独立配置表。 */
    public static function tableName(): string { return 'portal_settings'; }

    /** 校验公开字段并允许时间行为写入时间戳。 */
    public function rules(): array { return array_merge(SettingsRules::rules(), [[['create_time', 'update_time'], 'safe']]); }

    /** 提供一致的字段名称。 */
    public function attributeLabels(): array { return SettingsRules::labels(); }

    /** 自动记录修改时间。 */
    public function behaviors(): array { return [$this->timestampBehavior()]; }

    /** 并发更新必须匹配版本。 */
    public function optimisticLock(): string { return 'version'; }

    /** 读取唯一配置，未迁移时明确报错，避免悄悄回退旧广告。 */
    public static function current(): self
    {
        return self::findOne(1) ?? throw new \yii\base\InvalidConfigException('请先执行门户配置数据库迁移。');
    }

    /** 返回所有广告或仅已发布广告，保持配置顺序。 */
    public function ads(bool $publishedOnly = false): array
    {
        $ads = json_decode($this->ads_json, true) ?: [];
        return $publishedOnly ? array_values(array_filter($ads, static fn (array $ad): bool => $ad['enabled'] === 1)) : $ads;
    }

    /** 返回配置引用的图片标识，用于受控读取与所有权校验。 */
    public function images(bool $publishedOnly = false): array
    {
        return array_values(array_filter(array_merge([$this->banner, $this->icon, $this->ad_icon], array_column($this->ads($publishedOnly), 'cover'))));
    }
}
