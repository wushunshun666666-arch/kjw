<?php

namespace app\models\portal\game;

/** 彩种新增输入，仅声明允许提交的字段。 */
final class AddForm extends \common\models\FormModel
{
    public $code = '';
    public $name = '';
    public $description = '';
    public $ball_count = 3;
    public $ball_min = 0;
    public $ball_max = 9;
    public $sort_order = 0;
    public $enabled = 0;

    /** 校验表单输入与号码上下限。 */
    public function rules(): array
    {
        return [
            [['code'], 'required'], [['code'], 'match', 'pattern' => '/^[a-z][a-z0-9_-]{1,31}$/'],
            [['name'], 'required'], [['name'], 'string', 'max' => 64],
            [['description'], 'string', 'max' => 255],
            [['ball_count'], 'required'], [['ball_count'], 'integer', 'min' => 1, 'max' => 20],
            [['ball_min'], 'required'], [['ball_min'], 'integer', 'min' => 0, 'max' => 999],
            [['ball_max'], 'required'], [['ball_max'], 'integer', 'min' => 0, 'max' => 999], [['ball_max'], 'compare', 'compareAttribute' => 'ball_min', 'operator' => '>=', 'type' => 'number'],
            [['sort_order'], 'integer', 'min' => 0, 'max' => 99999],
            [['enabled'], 'required'], [['enabled'], 'in', 'range' => [0, 1]],
        ];
    }

    /** 提供管理界面的字段名。 */
    public function attributeLabels(): array { return ['code' => '彩种代码', 'name' => '彩种名称', 'description' => '彩种说明', 'ball_count' => '号码个数', 'ball_min' => '最小号码', 'ball_max' => '最大号码', 'sort_order' => '排序', 'enabled' => '是否发布']; }
}
