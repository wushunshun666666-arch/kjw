<?php

namespace app\models\portal;

/** 采集命令的彩种选择、轮询间隔与轮次边界。 */
final class CollectForm extends \common\models\FormModel
{
    public $game = 'all';
    public $interval = 5;
    public $rounds = 0;

    /** 限制命令参数，all 只枚举已配置的固定来源。 */
    public function rules(): array
    {
        return [
            [['game'], 'required'],
            [['game'], 'in', 'range' => array_merge(['all'], array_keys(\common\models\lottery\FeedCatalog::GAMES))],
            [['interval'], 'integer', 'min' => 5, 'max' => 300],
            [['rounds'], 'integer', 'min' => 0, 'max' => 1000000],
        ];
    }
}
