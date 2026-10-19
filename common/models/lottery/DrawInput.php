<?php

namespace common\models\lottery;

/** API 适配器与人工录入共用的标准数据边界，不假定上游协议。 */
final class DrawInput extends \common\models\FormModel
{
    public $game_id;
    public $issue;
    public $draw_time;
    public $numbers;
    public $source;

    /** 拒绝非标准期号、时间和号码格式。 */
    public function rules(): array
    {
        return [
            [['game_id', 'issue', 'draw_time', 'numbers', 'source'], 'required'],
            [['game_id'], 'integer', 'min' => 1],
            [['issue'], 'match', 'pattern' => '/^[a-zA-Z0-9_-]{1,64}$/'],
            [['draw_time'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['numbers'], 'string', 'max' => 255],
            [['numbers'], 'match', 'pattern' => '/^\d{1,3}(,\d{1,3}){0,19}$/'],
            [['source'], 'string', 'max' => 128],
        ];
    }

    /** 定义人工录入和接口文档字段名。 */
    public function attributeLabels(): array
    {
        return ['game_id' => '彩种', 'issue' => '开奖期号', 'draw_time' => '开奖时间（北京时间）',
            'numbers' => '开奖号码', 'source' => '数据来源'];
    }

    /** 说明规范化号码输入契约。 */
    public function attributeHints(): array
    {
        return ['numbers' => '按原顺序用英文逗号分隔，例如 1,2,3。', 'draw_time' => '格式：2026-10-18 12:00:00'];
    }
}
