<?php

/**
 * 后台新增会员表单模型。
 *
 * 负责接收和校验人工创建会员时提交的资料及上级推广码。
 */

namespace app\models\member;

use common\models\FormModel;
use common\models\member\Member;
use common\models\member\MemberModel;

/**
 * 后台新增会员表单。
 */
final class AddForm extends FormModel
{
    /** @var string|null 备用登录用户名 */
    public $username;

    /** @var string|null 备用登录密码 */
    public $password;

    /** @var string|null 确认密码 */
    public $passwordConfirm;

    /** @var string|null 会员昵称 */
    public $nickname;

    /** @var string|null 头像地址 */
    public $avatar;

    /** @var string|null 国际手机号码 */
    public $mobile;

    /** @var string|null 邮箱 */
    public $email;

    /** @var string|null 直属上级推广码 */
    public $parentInviteCode;

    /**
     * 返回新增会员表单验证规则。
     */
    public function rules(): array
    {
        return [
            [['username'], 'filter', 'filter' => [MemberModel::class, 'normalizeUsername']],
            [['mobile'], 'filter', 'filter' => [MemberModel::class, 'normalizeMobile']],
            [['email'], 'filter', 'filter' => [MemberModel::class, 'normalizeEmail']],
            [['parentInviteCode'], 'filter', 'filter' => [MemberModel::class, 'normalizeInviteCode']],
            [['username', 'password', 'passwordConfirm', 'nickname'], 'required'],
            [['username'], 'string', 'min' => 4, 'max' => 40],
            [
                ['username'],
                'match',
                'pattern' => '/^[a-zA-Z][a-zA-Z0-9_-]*$/',
                'message' => '只能包含英文字母、数字、中划线和下划线，并且以英文字母开头。',
            ],
            [['username'], 'unique', 'targetClass' => Member::class, 'targetAttribute' => 'username'],
            [['password'], 'string', 'min' => 8, 'max' => 72],
            [['password'], 'mylibs\yii\validators\PasswordValidator', 'type' => 'complex'],
            [['passwordConfirm'], 'compare', 'compareAttribute' => 'password', 'message' => '两次输入的密码不一致。'],
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
            [['mobile'], 'unique', 'targetClass' => Member::class, 'targetAttribute' => 'mobile'],
            [['email'], 'string', 'max' => 191],
            [['email'], 'email'],
            [['email'], 'unique', 'targetClass' => Member::class, 'targetAttribute' => 'email'],
            [['parentInviteCode'], 'string', 'length' => 10],
            [['parentInviteCode'], 'match', 'pattern' => '/^[A-Z0-9]+$/'],
            [
                ['parentInviteCode'],
                'exist',
                'targetClass' => Member::class,
                'targetAttribute' => ['parentInviteCode' => 'invite_code'],
            ],
        ];
    }

    /**
     * 返回表单字段的中文名称。
     */
    public function attributeLabels(): array
    {
        return [
            'username' => '用户名',
            'password' => '登录密码',
            'passwordConfirm' => '确认密码',
            'nickname' => '昵称',
            'avatar' => '头像地址',
            'mobile' => '手机号码',
            'email' => 'Email',
            'parentInviteCode' => '上级推广码',
        ];
    }

    /**
     * 返回后台录入提示。
     */
    public function attributeHints(): array
    {
        return [
            'username' => '创建后不可在后台修改。',
            'password' => '至少8位，且包含字母、数字和特殊字符。',
            'avatar' => '可填写社交平台返回的HTTP/HTTPS头像地址。',
            'mobile' => '选填；包含国际区号，例如 +8613800138000。',
            'parentInviteCode' => '选填；填写后，该推广归属关系将固定且不可修改。',
        ];
    }
}
