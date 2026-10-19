<?php
/**
 * UserSearchForm.php
 * @author suxh
 */

namespace app\models\system;

use common\models\FormModel;

class UserSearchForm extends FormModel
{
	public $id;
	public $username;
	public $name;
	public $departmentId;
	public $status;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['id', 'username', 'name', 'departmentId', 'status'], 'safe'],
		];
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
			'id'=>'用户ID',
			'username'=>'用户名',
			'name'=>'姓名',
			'departmentId'=>'所属部门',
			'status'=>'状态',
		];
	}
}