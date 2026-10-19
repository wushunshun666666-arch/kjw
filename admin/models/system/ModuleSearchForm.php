<?php
/**
 * ModuleSearchForm.php
 * @author xurs
 */

namespace app\models\system;

use common\models\FormModel;

class ModuleSearchForm extends FormModel
{
	public $id;
	public $categoryId;
	public $name;
	public $url;
	public $description;
	public $create_time;
    public $update_time;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['id', 'categoryId', 'name', 'url', 'description','create_time','update_time'], 'safe'],
		];
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
			'id'=>'模块ID',
			'categoryId'=>'模块类别',
			'name'=>'模块名称',
			'url'=>'模块URL',
			'description'=>'模块描述',
            'create_time'=>'创建时间',
			'update_time'=>'更新时间'
		];
	}
}