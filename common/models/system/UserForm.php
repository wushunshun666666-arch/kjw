<?php
/**
 * UserForm.php
 *
 * @author suxh
 */

namespace common\models\system;

use common\models\FormModel;

class UserForm extends FormModel
{
	public $id;
	public $username;
	public $name;
	public $password;
	public $tel;
	public $mobi;
	public $email;
	public $status;
	public $timeout;
	public $note;
	public $create_time;
	public $update_time;

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
            'id' => '用户ID',
            'username' => '用户名',
            'password' => '登录密码',
            'name' => '姓名',
            'tel' => '联系电话',
            'mobi' => '手机号码',
            'email' => 'Email',
            'status' => '状态',
            'timeout' => '登录超时时间',
            'note' => '备注',
            'create_time' => '添加时间',
            'update_time' => '更新时间',
        ];
	}
}