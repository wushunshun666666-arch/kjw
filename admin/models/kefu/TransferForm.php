<?php

/**
 * 客服会话转交表单。
 *
 * 校验目标后台客服用户ID。
 */

namespace app\models\kefu;

use common\models\FormModel;
use common\models\system\User;

/** 客服转交表单模型。 */
final class TransferForm extends FormModel
{
    /** @var int|null 目标客服用户ID */
    public $targetUserId;

    /** 返回转交表单验证规则。 */
    public function rules(): array
    {
        return [
            [['targetUserId'], 'required'],
            [['targetUserId'], 'integer', 'min' => 1],
            [['targetUserId'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
        ];
    }
}
