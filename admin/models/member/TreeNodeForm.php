<?php

/**
 * 代理树节点查询表单。
 *
 * 负责校验代理卡片树按需加载时传入的父节点参数。
 */

namespace app\models\member;

use common\models\FormModel;

/**
 * 代理树节点查询输入模型。
 */
final class TreeNodeForm extends FormModel
{
    /** @var int|string|null 卡片树约定的父节点参数 */
    public $node;

    /**
     * 返回无嵌套的表单名称。
     *
     * 卡片树以 ?node=会员ID 传递参数，不能使用Yii默认的嵌套表单参数名。
     */
    public function formName(): string
    {
        return '';
    }

    /**
     * 返回树节点查询参数验证规则。
     */
    public function rules(): array
    {
        return [
            [['node'], 'default', 'value' => 0],
            [['node'], 'integer', 'min' => 0],
        ];
    }

    /**
     * 获取规范化后的父级会员ID。
     *
     * @return int|null 0表示根会员，因此转换为null
     */
    public function getParentId(): ?int
    {
        $nodeId = (int) $this->node;

        return $nodeId > 0 ? $nodeId : null;
    }
}
