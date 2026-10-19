<?php
/**
 * UserAddForm.php
 * @author suxh
 */

namespace app\models\system;

use common\models\FormModel;
use common\models\Error;
use common\models\system\UserModel;

class UserAddForm extends FormModel
{
	public $username;
	public $name;
	public $department_id;
	public $tel;
	public $mobi;
	public $email;
	public $note;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['username', 'name', 'department_id', 'email'], 'required'],
			[['username'], 'string', 'max' => 20],
			[['username'], 'mylibs\yii\validators\UserNameValidator'],
			[['username'], 'validateUserName'],
            [['name'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 90],
            [['email'], 'email'],
            [['tel', 'mobi'], 'string', 'max' => 20],
            [['tel'], 'mylibs\yii\validators\TelephoneValidator'],
            [['mobi'], 'mylibs\yii\validators\TelephoneValidator', 'type'=>'mobi'],
            [['note'], 'string', 'max' => 255],
		];
	}

	public function validateUserName() {
		$res = UserModel::checkUserName($this->username);
		if (Error::isError($res)) {
			return $this->addError('username', $res->getError());
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
            'username' => '用户名',
            'name' => '姓名',
            'department_id' => '所属部门',
            'tel' => '固定电话',
            'mobi' => '手机号码',
            'email' => 'Email',
            'timeout' => '登录超时时间',
            'note' => '备注',
            'create_time' => '添加时间',
            'update_time' => '更新时间',
		];
	}

	public function attributeHints() {
		return [
			'tel' => '号码格式：0592-88888888-01，分机号可不填写。',
			'email' => '初始密码将发送到该邮箱，请填写真实有效的邮箱。',
		];
	}
}