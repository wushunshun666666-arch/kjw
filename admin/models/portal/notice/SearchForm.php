<?php

namespace app\models\portal\notice;

/** 公告查询条件。 */
final class SearchForm extends \common\models\FormModel
{
    public $title;
    public $enabled;

    /** 限制查询输入格式。 */
    public function rules(): array { return [[['title'], 'string', 'max' => 128], [['enabled'], 'in', 'range' => [0, 1]]]; }

    /** 返回查询字段名。 */
    public function attributeLabels(): array { return ['title' => '名称', 'enabled' => '发布状态']; }
}
