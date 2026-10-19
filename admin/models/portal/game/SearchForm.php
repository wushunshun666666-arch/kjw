<?php

namespace app\models\portal\game;

/** 彩种查询条件。 */
final class SearchForm extends \common\models\FormModel
{
    public $name;
    public $enabled;

    /** 限制查询输入格式。 */
    public function rules(): array { return [[['name'], 'string', 'max' => 128], [['enabled'], 'in', 'range' => [0, 1]]]; }

    /** 返回查询字段名。 */
    public function attributeLabels(): array { return ['name' => '名称', 'enabled' => '发布状态']; }
}
