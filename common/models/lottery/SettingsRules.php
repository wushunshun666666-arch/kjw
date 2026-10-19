<?php

namespace common\models\lottery;

use common\validators\PortalAdsValidator;
use common\validators\PortalImageValidator;

/** 前后台共用的站点配置格式规则。 */
final class SettingsRules
{
    /** 可修改的公开字段，禁止请求覆盖主键和审计字段。 */
    public const FIELDS = ['website', 'title', 'landing', 'ad_wx', 'ad_ww', 'banner', 'icon', 'ad_icon', 'show_qrcode', 'notice_scroll', 'ads_json'];

    /** 返回仅检查输入格式的 Yii 规则。 */
    public static function rules(): array
    {
        return [
            [['website', 'title', 'landing', 'banner', 'icon', 'ads_json', 'version', 'show_qrcode'], 'required'],
            [['website', 'title'], 'string', 'max' => 128],
            // 登录链接需能完整编码进二维码，按字节限制也覆盖多字节 URL。
            [['landing'], 'string', 'max' => 2048, 'encoding' => '8bit'],
            [['ad_wx', 'ad_ww'], 'string', 'max' => 2048],
            [['landing', 'ad_wx', 'ad_ww'], 'url', 'validSchemes' => ['http', 'https'], 'enableIDN' => true],
            [['banner', 'icon', 'ad_icon'], PortalImageValidator::class],
            [['show_qrcode'], 'in', 'range' => [0, 1]],
            [['notice_scroll'], 'string', 'max' => 2000],
            [['ads_json'], 'string', 'max' => 60000],
            [['ads_json'], PortalAdsValidator::class],
            [['version'], 'integer', 'min' => 0],
        ];
    }

    /** 提供管理表单的字段名称。 */
    public static function labels(): array
    {
        return ['website' => '顶部显示域名', 'title' => '站点标题', 'landing' => '登录 / 注册链接',
            'ad_wx' => '合作联系链接', 'ad_ww' => '备用联系链接', 'banner' => '顶部横幅图片',
            'icon' => '站点图标', 'ad_icon' => '联系图标', 'show_qrcode' => '显示登录二维码',
            'notice_scroll' => '滚动公告', 'ads_json' => '广告列表', 'version' => '配置版本'];
    }
}
