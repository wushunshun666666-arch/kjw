<?php

namespace common\models\system;

/**
 * This is the model class for table "category".
 *
 * @property integer $id
 * @property string $name
 * @property integer $type
 * @property integer $parent_id
 * @property integer $level
 * @property integer $orderby
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
class Category extends \common\models\ActiveRecord
{
	public static $CATEGORY_TYPE = null;

	/**
	 * 类别类型(模块类别1000)
	 */
	const TYPE_1000 = 1000;

	/**
	 * 类别排序默认值
	 */
	const OrderBy = 0;

	/**
	 * 状态值 0正常
	 */
	const Status_0 =0;

	/**
	 * 系统保留类别
	 */
	const Status_1 =1;

	/**
	 * 类别未分类
	 */
	const Status_2 =2;

	/**
	 * 类别级别
	 */
	const Level3  = 3;

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'category';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name', 'type', 'parent_id', 'level', 'orderby', 'status'], 'required'],
			[['type', 'parent_id', 'level', 'orderby', 'status'], 'integer'],
			[['create_time', 'update_time'], 'safe'],
			[['name'], 'string', 'max' => 60]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '类别ID',
			'name' => '类别名称',
			'type' => '类别类型(模块类别1000)',
			'parent_id' => '父类别ID',
			'level' => '类别级别',
			'orderby' => '类别排序',
			'status' => '状态(0正常，1系统保留类别，2未分类)',
			'create_time' => '创建时间',
			'update_time' => '更新时间',
		];
	}

	/**
	 * @inheritdoc
	 */
	public function behaviors() {
		return [
			$this->timestampBehavior(),
		];
	}

	public static function getTree($callback=null) {
		if ($callback == null) {
			$callback = function($item) {
				return [
					'id' => $item->id,
					'name' => $item->name,
					'status' => $item->status,
				];
			};
		}

		$items = static::findAllSorted();

		$trees = [];
		$index = [];
		$root = &$trees;
		foreach ($items as $item) {
			if ($item->level > 0) {
				if (!isset($index[$item->parent_id])) {
					continue;
				}
				$parent = &$index[$item->parent_id];
				if (!isset($parent['children'])) {
					$parent['children'] = [];
				}
				$root = &$parent['children'];
			}
			$root[] = $callback($item);
			$index[$item->id] = &$root[count($root)-1];
		}

		return $trees;
	}

	public static function findAllSorted() {
		$query = static::find();
		$query->filterWhere(['type'=>static::$CATEGORY_TYPE]);
		$query->orderBy([
			'level'=>SORT_ASC,
			'orderby'=>SORT_ASC,
			'id'=>SORT_ASC,
		]);
		return $query->all();
	}

	public static function findParentName($id){
		 $model = static::find()->where(['id' => $id])->one();
		 return $model['name'];
	}

	/**
	 * 检查是否已经存在同级别(同一父级别下)的同名类别，返回布尔值
	 * @param type $name
	 * @param type $id
	 * @return type
	 */
	public static function extstsByName($name,$id){
		return static::find()->where(['name' => $name])->andWhere(['parent_id'=>$id])->exists();
	}

	/**
	 * 检查当前类别下还有没有子类别
	 * @param type $id
	 * @return type
	 */
	public static function existsByParentId($id){
		return static::find() ->where(['parent_id'=> $id])->exists();
	}

	/**
	 * 系统验证类别下是否有模块
	 * @param type $id
	 * @return boolean
	 */
	public static function existsByModule($id){
		return module::find()->where(['category_id'=>$id])->exists();
	}

	public static function extstsByStatus($id){
		if($id ==  Category::Status_1){
			 return  true;
		 }else{
			 return false;
		 }
	}

	/**
	 * 查找父类的级别，并判断级别是否合法
	 * @param type $id
	 * @return boolean
	 */
	public static function biggerLevel($id){
		$model = static::find()->where(['id' => $id])->one();
		if($model['level']>=Category::Level3){
			 return true;
		}else{
			 return false;
		 }
	}

	public static function findLevel($id){
		 $model = static::find()->where(['id' => $id])->one();
		 return $model['level'];
	}

	public static function findById($id) {
		return static::find()->where(['id' => $id])->one();
	}

	public static function findStatus($id){
		return static::find()->where(['id'=>$id])->asArray()->one();
	}
}