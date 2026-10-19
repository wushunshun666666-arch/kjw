<?php

/**
 * 客服快捷回复编辑表单。
 *
 * 用于新增和修改全平台客服共用的文字及表情短语。
 */

namespace app\models\kefu;

use common\models\FormModel;
use common\models\kefu\KefuQuickReply;

/** 客服快捷回复编辑表单模型。 */
final class QuickReplyForm extends FormModel
{
    /** @var int|null 快捷回复ID，新增时为空 */
    public $id;

    /** @var string|null 短语标题 */
    public $title;

    /** @var string|null 回复文字和表情内容 */
    public $content;

    /** @var int 快捷回复排序值 */
    public $orderby = 0;

    /** @var int 快捷回复状态 */
    public $status = KefuQuickReply::STATUS_ACTIVE;

    /** 返回快捷回复编辑验证规则。 */
    public function rules(): array
    {
        return [
            [['title', 'content', 'status'], 'required'],
            [['id'], 'integer', 'min' => 1],
            [['orderby'], 'integer'],
            [['status'], 'in', 'range' => array_keys(KefuQuickReply::getStatusNames())],
            [['title'], 'string', 'max' => 60],
            [['content'], 'string', 'max' => 5000],
        ];
    }

    /** 返回快捷回复字段中文名称。 */
    public function attributeLabels(): array
    {
        return [
            'id' => '快捷回复ID',
            'title' => '短语标题',
            'content' => '回复内容',
            'orderby' => '排序值',
            'status' => '状态',
        ];
    }
}
