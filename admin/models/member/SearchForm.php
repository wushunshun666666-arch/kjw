<?php

/**
 * 后台会员列表搜索表单模型。
 *
 * 负责校验会员列表允许使用的筛选条件。
 */

namespace app\models\member;

use common\models\FormModel;
use common\models\member\Member;

/**
 * 会员列表搜索表单。
 */
final class SearchForm extends FormModel
{
    /** @var int|null 会员ID筛选值 */
    public $id;

    /** @var string|null 用户名模糊筛选值 */
    public $username;

    /** @var string|null 昵称模糊筛选值 */
    public $nickname;

    /** @var string|null 手机号码模糊筛选值 */
    public $mobile;

    /** @var string|null 邮箱模糊筛选值 */
    public $email;

    /** @var int|null 会员状态筛选值 */
    public $status;

    /** @var int|null 注册方式筛选值 */
    public $registerType;

    /**
     * 返回会员搜索条件验证规则。
     */
    public function rules(): array
    {
        return [
            [['id'], 'integer', 'min' => 1],
            [['username'], 'string', 'max' => 40],
            [['nickname'], 'string', 'max' => 60],
            [['mobile'], 'string', 'max' => 32],
            [['email'], 'string', 'max' => 191],
            [['status'], 'in', 'range' => array_keys(Member::getStatusNames())],
            [['registerType'], 'in', 'range' => array_keys(Member::getRegisterTypeNames())],
            [['id', 'username', 'nickname', 'mobile', 'email', 'status', 'registerType'], 'safe'],
        ];
    }

    /**
     * 返回搜索字段的中文名称。
     */
    public function attributeLabels(): array
    {
        return [
            'id' => '会员ID',
            'username' => '用户名',
            'nickname' => '昵称',
            'mobile' => '手机号',
            'email' => 'Email',
            'status' => '状态',
            'registerType' => '注册方式',
        ];
    }
}
