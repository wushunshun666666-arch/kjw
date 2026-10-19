<?php

namespace common\models\system;

/**
 * This is the model class for table "module".
 *
 * @property integer $id
 * @property integer $category_id
 * @property string $name
 * @property string $url
 * @property string $description
 * @property string $create_time
 * @property string $update_time
 */
class Module extends \common\models\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'module';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['category_id', 'name', 'url'], 'required'],
			[['category_id'], 'integer'],
			[['create_time', 'update_time'], 'safe'],
			[['name'], 'string', 'max' => 60],
			[['url'], 'string', 'max' => 225],
			[['description'], 'string', 'max' => 255],
			[['url'], 'unique']
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '模块ID',
			'category_id' => '模块类别',
			'name' => '模块名称',
			'url' => '模块url',
			'description' => '模块描述',
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

	public static function getByUrl($url) {
		return static::find()->where(['url' => $url])->one();
	}

	public static function getUserRoleModules($userId) {
		$tableModule = Module::tableName();
		$tableRoleModule = RoleModule::tableName();
		$tableUserRole = UserRole::tableName();

		$db = self::getDb();
		$sql = "SELECT a.id,a.url,a.name FROM {$tableModule} a
				LEFT JOIN {$tableRoleModule} b ON a.id = b.module_id
				LEFT JOIN {$tableUserRole} c ON b.role_id = c.role_id
				WHERE c.user_id = :userId";

		$command = $db->createCommand($sql, [':userId'=>$userId]);
		return $command->queryAll();
	}

	public static function hasUserRoleModule($userId, $moduleId) {
		$tableModule = Module::tableName();
		$tableRoleModule = RoleModule::tableName();
		$tableUserRole = UserRole::tableName();

		$db = self::getDb();
		$sql = "SELECT COUNT(a.id) AS num FROM {$tableModule} a
		LEFT JOIN {$tableRoleModule} b ON a.id = b.module_id
		LEFT JOIN {$tableUserRole} c ON b.role_id = c.role_id
		WHERE a.id = :moduleId AND c.user_id = :userId";

		$command = $db->createCommand($sql, [':userId'=>$userId, ':moduleId'=>$moduleId]);
		$row = $command->queryOne();

		return ($row && $row['num'] > 0);
	}

	public static function findAllSorted() {
		$query = static::find();
		$query->orderBy([
			'id'=>SORT_ASC,
		]);
		return $query->all();
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getCategory() {
		return $this->hasOne(Category::className(), ['id' => 'category_id']);
	}

	public static function getAllNamesGroupByCategory() {
		$modules = [];
		$items = self::findAllSorted();
		foreach ($items as $module) {
			$categoryId = $module->category_id;
			if (!isset($modules[$categoryId])) {
				$modules[$categoryId] = [];
			}
			$modules[$categoryId][] = [
				'id' => $module->id,
				'name' => $module->name,
			];
		}

		return $modules;
	}

	public static function findById($id) {
		return static::find()->where(['id' => $id])->one();
	}

	/**
	 * 判断系统里面是否已经存在当前的url 返回布尔值。
	 * @param type $url
	 * @param string $id 排除检查当前的id
	 * @return type
	 */
	public static function extstsByUrl($url,$id=''){
		$query = static::find();
		$query->where(['url' => $url]);
		if ($id != '') {
			$query->andWhere(['<>', 'id', $id]);
		}
		return $query->exists();
	}

}