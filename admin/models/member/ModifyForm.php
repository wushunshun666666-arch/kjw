<?php

/**
 * 后台修改会员表单模型。
 *
 * 只允许维护会员基础资料，不允许修改用户名和推广归属。
 */

namespace app\models\member;

use common\models\FormModel;
use common\models\member\Member;
use common\models\member\MemberModel;

/**
 * 后台修改会员表单。
 */
final class ModifyForm extends FormModel
{
    /** @var int|null 会员ID */
    public $id;

    /** @var string|null 只读备用登录用户名 */
    public $username;

    /** @var string|null 会员昵称 */
    public $nickname;

    /** @var string|null 头像地址 */
    public $avatar;

    /** @var string|null 国际手机号码 */
    public $mobile;

    /** @var string|null 邮箱 */
    public $email;

    /**
     * 返回修改会员表单验证规则。
     */
    public function rules(): array
    {
        return [
            [['mobile'], 'filter', 'filter' => [MemberModel::class, 'normalizeMobile']],
            [['email'], 'filter', 'filter' => [MemberModel::class, 'normalizeEmail']],
            [['id'], 'integer', 'min' => 1],
            [['id', 'username'], 'safe'],
            [['nickname'], 'required'],
            [['username'], 'string', 'max' => 40],
            [['nickname'], 'string', 'max' => 60],
            [['avatar'], 'string', 'max' => 500],
            [['avatar'], 'url', 'validSchemes' => ['http', 'https']],
            [['mobile'], 'string', 'max' => 32],
            [
                ['mobile'],
                'match',
                'pattern' => '/^\+[1-9]\d{6,14}$/',
                'message' => '请输入包含国际区号的手机号码，例如 +8613800138000。',
            ],
            [
                ['mobile'],
                'unique',
                'targetClass' => Member::class,
                'targetAttribute' => 'mobile',
                'filter' => fn ($query) => $query->andWhere(['<>', 'id', (int) $this->id]),
            ],
            [['email'], 'string', 'max' => 191],
            [['email'], 'email'],
            [
                ['email'],
                'unique',
                'targetClass' => Member::class,
                'targetAttribute' => 'email',
                'filter' => fn ($query) => $query->andWhere(['<>', 'id', (int) $this->id]),
            ],
        ];
    }

    /**
     * 返回表单字段的中文名称。
     */
    public function attributeLabels(): array
    {
        return [
            'id' => '会员ID',
            'username' => '用户名',
            'nickname' => '昵称',
            'avatar' => '头像地址',
            'mobile' => '手机号码',
            'email' => 'Email',
        ];
    }

    /**
     * 返回后台录入提示。
     */
    public function attributeHints(): array
    {
        return [
            'avatar' => '可填写社交平台返回的HTTP/HTTPS头像地址。',
            'mobile' => '选填；包含国际区号，例如 +8613800138000。',
        ];
    }
}
