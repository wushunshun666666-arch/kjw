<?php

namespace frontend\models;

/** 公共历史查询条件，限制日期、期号和页码。 */
final class HistoryForm extends \common\models\FormModel
{
    public $game = '';
    public $date = '';
    public $issue = '';
    public $page = 1;

    /** URL 使用平铺参数。 */
    public function formName(): string { return ''; }

    /** 约束公开查询输入。 */
    public function rules(): array
    {
        return [
            [['game'], 'match', 'pattern' => '/^[a-z][a-z0-9_-]{1,31}$/'],
            [['date'], 'date', 'format' => 'php:Y-m-d'],
            [['issue'], 'match', 'pattern' => '/^[a-zA-Z0-9_-]{1,64}$/'],
            [['page'], 'integer', 'min' => 1, 'max' => 10000],
        ];
    }
}
