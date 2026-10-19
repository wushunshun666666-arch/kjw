<?php

namespace common\models\system;

/**
 * This is the model class for table "user_role".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $role_id
 */
class UserRole extends \common\models\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'user_role';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['user_id', 'role_id'], 'required'],
			[['user_id', 'role_id'], 'integer'],
			[['role_id', 'user_id'], 'unique', 'targetAttribute' => ['role_id', 'user_id'], 'message' => 'The combination of User ID and Role ID has already been taken.']
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'user_id' => 'User ID',
			'role_id' => 'Role ID',
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUser() {
		return $this->hasOne(User::className(), ['id' => 'user_id']);
	}

	public static function existsByRoleId($roleId) {
		return static::find()->where(['role_id'=>$roleId])->exists();
	}

	public static function getRoleIdsOfUser($userId) {
		$query = static::find();
		$query->select('role_id');
		$query->where(['user_id'=>$userId]);
		$query->asArray();
		$items = $query->all();
		$ids = [];
		foreach ($items as $item) {
			$ids[] = $item['role_id'];
		}

		return $ids;
	}

	public static function batchInsert($userId, $roleIds) {
		$userId = intval($userId);
		foreach ($roleIds as $roleId) {
			$roleId = intval($roleId);
			$rows[] = [0, $userId, $roleId];
		}

		$table = self::tableName();
		$db = self::getDb();

		$command = $db->createCommand();
		$command->batchInsert($table, ['id', 'user_id', 'role_id'], $rows);
		return $command->execute();
	}

	public static function batchDelete($userId, $roleIds) {
		return self::deleteAll([
			'and',
			['user_id'=>$userId],
			['in', 'role_id', $roleIds]
		]);
	}
}