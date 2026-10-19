<?php

/**
 * 客服快捷回复搜索表单。
 */

namespace app\models\kefu;

use common\models\FormModel;
use common\models\kefu\KefuQuickReply;

/** 客服快捷回复列表搜索模型。 */
final class QuickReplySearchForm extends FormModel
{
    /** @var string|null 标题或内容关键词 */
    public $keyword;

    /** @var int|null 快捷回复状态 */
    public $status;

    /** 返回快捷回复搜索验证规则。 */
    public function rules(): array
    {
        return [
            [['keyword'], 'string', 'max' => 191],
            [['status'], 'in', 'range' => array_keys(KefuQuickReply::getStatusNames())],
        ];
    }

    /** 返回搜索字段中文名称。 */
    public function attributeLabels(): array
    {
        return ['keyword' => '关键词', 'status' => '状态'];
    }
}
