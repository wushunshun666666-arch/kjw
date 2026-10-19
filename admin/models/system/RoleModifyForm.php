<?php
/**
 * RoleModifyForm.php
 * @author suxh
 */

namespace app\models\system;

use common\models\FormModel;
use common\models\Error;
use common\models\system\RoleModel;
use mylibs\yii\validators\NameValidator;

class RoleModifyForm extends FormModel
{
	public $id;
	public $name;
	public $description;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['name'], 'required'],
            [['name'], 'string', 'max' => 20],
            [['name'], 'mylibs\yii\validators\NameValidator'],
            [['name'], 'validateName'],
            [['description'], 'string', 'max' => 255],
            [['id'], 'safe'],
		];
	}

	public function validateName() {
		$res = RoleModel::checkName($this->name, $this->id);
		if (Error::isError($res)) {
			return $this->addError('name', $res->getError());
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
            'name' => '角色名称',
            'description' => '角色描述',
		];
	}

	public function attributeHints() {
		return [

		];
	}
}