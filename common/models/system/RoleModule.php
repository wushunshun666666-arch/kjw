<?php

namespace common\models\system;

/**
 * This is the model class for table "role_module".
 *
 * @property integer $id
 * @property integer $role_id
 * @property integer $module_id
 */
class RoleModule extends \common\models\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'role_module';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['role_id', 'module_id'], 'required'],
			[['role_id', 'module_id'], 'integer'],
			[['role_id', 'module_id'], 'unique', 'targetAttribute' => ['role_id', 'module_id'], 'message' => 'The combination of 角色编号 and 模块编号 has already been taken.']
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '角色模块编号',
			'role_id' => '角色编号',
			'module_id' => '模块编号',
		];
	}

	public static function existsByRoleId($roleId) {
		return static::find()->where(['role_id'=>$roleId])->exists();
	}

	public static function existsByModuleId($moduleId){
		return static::find()->where(['module_id'=>$moduleId])->exists();
	}

	public static function getIdsOfRole($roleId) {
		$query = static::find();
		$query->select('module_id');
		$query->where(['role_id'=>$roleId]);
		$query->asArray();
		$items = $query->all();
		$ids = [];
		foreach ($items as $item) {
			$ids[] = $item['module_id'];
		}

		return $ids;
	}

	public static function batchInsert($roleId, $moduleIds) {
		$table = self::tableName();
		$db = self::getDb();

		$roleId = intval($roleId);
		foreach ($moduleIds as $moduleId) {
			$moduleId = intval($moduleId);
			$values[] = [0,$roleId,$moduleId];
		}

		$command = $db->createCommand();
		$command->batchInsert($table, ['id', 'role_id', 'module_id'], $values);
		return $command->execute();
	}

	public static function batchDelete($roleId, $moduleIds) {
		return self::deleteAll([
			'and',
			['role_id'=>$roleId],
			['in', 'module_id', $moduleIds]
		]);
	}

	public function getRole()
	{
		return $this->hasOne(Role::className(), ['id' => 'role_id']);
	}
}