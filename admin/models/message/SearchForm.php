<?php

namespace app\models\message;

use common\models\FormModel;
use common\models\notice\Notice;

/** 后台站内消息列表搜索输入模型。 */
final class SearchForm extends FormModel
{
    /** @var string|null 未读条件或消息类型 */
    public $condition;

    /** 返回站内消息搜索条件验证规则。 */
    public function rules(): array
    {
        $conditions = array_map('strval', array_keys(Notice::getTypeNames()));
        $conditions[] = MessageService::CONDITION_UNREAD;
        return [[['condition'], 'in', 'range' => $conditions]];
    }

    /** 返回搜索字段中文名称。 */
    public function attributeLabels(): array
    {
        return ['condition' => '消息条件'];
    }
}
