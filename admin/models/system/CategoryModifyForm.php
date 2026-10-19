<?php
/**
 * CategoryModifyForm.php
 * @author xursh
 */

namespace app\models\system;

use common\models\FormModel;
use common\models\Error;
use common\models\system\CategoryModel;

class CategoryModifyForm extends FormModel
{
    public $name;
    public $parent_id;
	public $status;
    
    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['name','parent_id'], 'required'],
			[['name'], 'string', 'max' => 30],
			[['name'], 'validateName'],
			[['name'], 'mylibs\yii\validators\CompanyNameValidator'],
		
		];
    }

	public function validateName() {
		
        $res = CategoryModel::checkName($this->name, $this->parent_id);
		if (Error::isError($res)) {
            return  $this->addError('name', $res->getError());
        }
    }
	
	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
			'parentName' =>'父类名称',
            'parent_id' => '父类ID',
            'name' => '类别名称',
			'status' => '类别状态'
		];
	}


}