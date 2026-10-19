<?php
/**
 * RoleSearchForm.php
 * @author suxh
 */

namespace app\models\system;

use common\models\FormModel;

class RoleSearchForm extends FormModel
{
	public $name;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['name'], 'safe'],
		];
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
			'name'=>'角色名称',
		];
	}
}