<?php

namespace common\models\system;

/**
 * This is the model class for table "department".
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string $name
 * @property integer $status
 * @property string $description
 * @property string $create_time
 * @property string $update_time
 *
 * @property User[] $users
 */
class Department extends \common\models\ActiveRecord
{
	/**
	 * @var integer 状态-有效
	 */
	const STATUS_1000 = 1000;
	/**
	 * @var integer 状态-已删除
	 */
	const STATUS_9000 = 9000;

	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'department';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['parent_id', 'status'], 'integer'],
			[['name', 'status'], 'required'],
			[['create_time', 'update_time'], 'safe'],
			[['name'], 'string', 'max' => 30],
			[['description'], 'string', 'max' => 255]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'parent_id' => '父部门ID',
			'name' => '部门名称',
			'status' => '状态(1000正常，9000已删除)',
			'description' => '部门说明',
			'create_time' => '添加时间',
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

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(User::className(), ['department_id' => 'id']);
	}

	/**
	 * 取得组织名称树
	 * @param integer $rootId 组织树根节点ID
	 * @return array 返回组织名称树([['id'=>id,'name'=>name,'level'=>level,'children'=>[...]],...])
	 */
	public static function getTree($rootId = 0) {
		$departments = static::find()->where(['status'=>self::STATUS_1000])
		->orderBy(['parent_id'=>SORT_ASC, 'CONVERT(name using gbk)'=>SORT_ASC])
		->all();

		$trees = [];
		self::buildTree($trees, $rootId, 0, $departments);

		return  $trees;
	}

	private static function buildTree(&$trees, $parentId, $level, $departments) {
		foreach ($departments as $department) {
			$isNode = false;
			if ($parentId > 0 && $level == 0) {
				if ($department->id == $parentId) {
					$isNode = true;
				}
			}
			else if ($department->parent_id == $parentId) {
				$isNode = true;
			}
			if ($isNode) {
				$trees[] = [
					'id' => $department->id,
					'name'=>$department->name,
					'level'=>$level,
					'children'=>[],
				];
				self::buildTree($trees[count($trees)-1]['children'], $department->id, $level+1, $departments);
			}
		}
	}

	/**
	 * 取得指定部门下的所有子部门
	 * @param integer $rootId
	 * @return array 返回所有子部门，包括$rootId本身
	 */
	public static function getChildIds($rootId) {
		$list = [$rootId];

		$departments = static::find()->where(['status'=>self::STATUS_1000])->all();

		self::recChildIds($list, $rootId, $departments);

		return $list;
	}

	private static function recChildIds(&$list, $parentId, $departments) {
		foreach ($departments as $department) {
			if ($department->parent_id == $parentId) {
				$list[] = $department->id;
				self::recChildIds($list, $department->id, $departments);
			}
		}
	}
}