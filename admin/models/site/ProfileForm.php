<?php
/**
 * ProfileForm.php
 * @author suxh
 */

namespace app\models\site;

use common\models\FormModel;

class ProfileForm extends FormModel
{
	public $id;
	public $username;
	public $name;
	public $department;
	public $tel;
	public $mobi;
	public $email;
	public $note;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['id', 'username', 'name', 'department'], 'safe'],
			[['email'], 'required'],
            [['email'], 'string', 'max' => 90],
            [['email'], 'email'],
            [['tel', 'mobi'], 'string', 'max' => 20],
            [['tel'], 'mylibs\yii\validators\TelephoneValidator'],
            [['mobi'], 'mylibs\yii\validators\TelephoneValidator', 'type'=>'mobi'],
            [['note'], 'string', 'max' => 255],
		];
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
			'id' => '用户ID',
            'username' => '用户名',
            'name' => '姓名',
            'department' => '所属部门',
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
			'tel' => '号码格式：0592-88888888-01，分机号可不填写',
		];
	}
}