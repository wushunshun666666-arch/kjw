<?php

namespace app\models\portal\settings;

use common\models\lottery\SettingsRules;

/** 网站设置输入，只校验格式，不查询数据库或保存文件。 */
final class SettingsForm extends \common\models\FormModel
{
    public $website = '';
    public $title = '';
    public $landing = '';
    public $ad_wx = '';
    public $ad_ww = '';
    public $banner = '';
    public $icon = '';
    public $ad_icon = '';
    public $show_qrcode = 1;
    public $notice_scroll = '';
    public $ads_json = '[]';
    public $version = 0;

    /** 使用领域格式规则。 */
    public function rules(): array { return SettingsRules::rules(); }

    /** 提供中文字段名。 */
    public function attributeLabels(): array { return SettingsRules::labels(); }
}
