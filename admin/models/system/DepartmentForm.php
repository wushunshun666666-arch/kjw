<?php
/**
 * Department.php
 * @author wudf
 */
namespace app\models\system;

use common\models\FormModel;
use common\models\Error;
use common\models\system\DepartmentModel;

class DepartmentForm extends FormModel
{
	public $id;
	public $name;


	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['id','name'], 'required'],
		    [['name'], 'validateDepartmentName'], 
		];
	}
	/**
	 * 部门名称查重
	 */
	public function validateDepartmentName() {
		$res = DepartmentModel::checkName($this->id,$this->name);
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
			'id' => '上级部门',
		    'departmentname'=>'上级部门',
            'name' => '部门名称',
		];
	}
  
	
}