<?php

namespace frontend\models;

/** 公开彩种入口边界，仅接受本站接入的来源代码。 */
final class GameForm extends \common\models\FormModel
{
    public $game = 'jnd28';

    /** 首页与轮询接口共用相同参数名称。 */
    public function formName(): string { return ''; }

    /** 拒绝数组、未知代码及任意路径。 */
    public function rules(): array
    {
        return [[['game'], 'required'], [['game'], 'in', 'range' => array_keys(\common\models\lottery\FeedCatalog::GAMES)]];
    }
}
