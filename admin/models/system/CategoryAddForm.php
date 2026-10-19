<?php
/**
 * ModuleAddForm.php
 * @author xursh
 */

namespace app\models\system;

use common\models\FormModel;
use common\models\Error;
use common\models\system\CategoryModel;

class CategoryAddForm extends FormModel
{
    public $name;
    public $parentId;
    
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['name','parentId'], 'required'],
			[['name'], 'string', 'max' => 30],
			[['name'], 'mylibs\yii\validators\CompanyNameValidator'],
			[['name'], 'validateName'],
		
		];
    }

	public function validateName() {
        $res = CategoryModel::checkName($this->name, $this->parentId);
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
			'parentName' =>'父类名称',
            'name' => '类别名称',
		];
	}


}