<?php

namespace common\models\system;

/**
 * This is the model class for table "role".
 *
 * @property integer $id
 * @property string $name
 * @property string $description
 * @property string $create_time
 * @property string $update_time
 */
class Role extends \common\models\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'role';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name'], 'required'],
			[['create_time', 'update_time'], 'safe'],
			[['name'], 'string', 'max' => 30],
			[['description'], 'string', 'max' => 255],
			[['name'], 'unique']
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '角色ID',
			'name' => '角色名称',
			'description' => '描述',
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

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getModules() {
		return $this->hasMany(RoleModule::className(), ['role_id' => 'id']);
	}

	public static function findById($id) {
		return static::find()->where(['id' => $id])->one();
	}

	/**
	 * 查找名称是否已经存在
	 * @param string $name
	 * @return boolean 返回名称是否存在
	 */
	public static function existsByName($name, $excludeId=null) {
		$query = static::find()->where(['name' => $name]);
		$query->andFilterWhere(['<>', 'id', $excludeId]);
		return $query->exists();
	}

	public static function getAllNames() {
		$query = static::find();
		$items = $query->all();

		$names = [];
		foreach ($items as $item) {
			$names[$item->id] = $item->name;
		}

		return $names;
	}
}