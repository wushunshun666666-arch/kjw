<?php

namespace app\models\portal\draw;

/** 后台仅允许采集白名单中的单个来源。 */
final class CollectForm extends \common\models\FormModel
{
    public $game = 'jnd28';

    /** 不允许通过采集按钮传入 URL 或任意彩种代码。 */
    public function rules(): array
    {
        return [[['game'], 'required'], [['game'], 'in', 'range' => array_keys(\common\models\lottery\FeedCatalog::GAMES)]];
    }
}
