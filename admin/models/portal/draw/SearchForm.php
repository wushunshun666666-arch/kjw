<?php

namespace app\models\portal\draw;

/** 开奖记录搜索边界。 */
final class SearchForm extends \common\models\FormModel
{
    public $game_id;
    public $issue;

    /** 验证搜索彩种和期号。 */
    public function rules(): array
    {
        return [[['game_id'], 'integer', 'min' => 1], [['issue'], 'string', 'max' => 64]];
    }

    /** 返回搜索字段名。 */
    public function attributeLabels(): array { return ['game_id' => '彩种', 'issue' => '期号']; }
}
