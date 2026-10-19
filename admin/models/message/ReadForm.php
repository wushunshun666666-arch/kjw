<?php

namespace app\models\message;

use common\models\FormModel;

/** 后台站内消息阅读动作输入模型。 */
final class ReadForm extends FormModel
{
    /** @var int|null 需要标记为已读的消息ID */
    public $id;

    /** 返回阅读动作验证规则。 */
    public function rules(): array
    {
        return [[['id'], 'required'], [['id'], 'integer', 'min' => 1]];
    }

    /** 返回阅读字段中文名称。 */
    public function attributeLabels(): array
    {
        return ['id' => '消息ID'];
    }
}
