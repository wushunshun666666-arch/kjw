<?php

namespace app\models\portal\notice;

/** 公告新增输入，仅声明允许提交的字段。 */
final class AddForm extends \common\models\FormModel
{
    public $title = '';
    public $content = '';
    public $enabled = 0;

    /** 校验表单输入与号码上下限。 */
    public function rules(): array
    {
        return [
            [['title'], 'required'], [['title'], 'string', 'max' => 128],
            [['content'], 'required'], [['content'], 'string', 'max' => 20000],
            [['enabled'], 'required'], [['enabled'], 'in', 'range' => [0, 1]],
        ];
    }

    /** 提供管理界面的字段名。 */
    public function attributeLabels(): array { return ['title' => '标题', 'content' => '正文', 'enabled' => '是否发布']; }
}
