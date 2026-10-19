<?php
/**
 * CategorySearchForm.php
 * @author xu
 */

namespace app\models\system;

use common\models\FormModel;

class CategorySearchForm extends FormModel
{
	public $id;
	public $name;
	public $type;
	public $parent_id;
	public $level;
	public $orderby;
	public $status;

	/**
	 * @inheritdoc
	 */
	public function rules() {
		return [
			[['id'], 'safe'],
		];
	}

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Model::attributeLabels()
	 */
	public function attributeLabels() {
		return [
			'id'=>'类别ID',
			'name'=>'类别名称',
			'type'=>'类别类型',
			'orderby'=>'类别排序',
			'level' => '类别级别',
			'status'=>'状态',
		];
	}
}