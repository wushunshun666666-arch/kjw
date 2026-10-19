<?php

namespace common\models\system;

/**
 * This is the model class for table "allow_login_ip".
 *
 * @property integer $id
 * @property string $ip
 * @property string $ownership
 * @property string $note
 * @property string $create_time
 * @property string $update_time
 */
class AllowLoginIp extends \common\models\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'allow_login_ip';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['ip', 'ownership'], 'required'],
			[['create_time', 'update_time'], 'safe'],
			[['ip'], 'string', 'max' => 20],
			[['ownership'], 'string', 'max' => 60],
			[['note'], 'string', 'max' => 255],
			[['ip'], 'unique'],
		];
	}

	public function behaviors()
	{
		return [$this->timestampBehavior()];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => '记录ID',
			'ip' => 'IP',
			'ownership' => 'IP归属',
			'note' => '备注',
			'create_time' => '添加时间',
			'update_time' => '更新时间',
		];
	}

	/**
	 * 检查IP是否在列表中
	 * @param $ip 要检查的IP地址
	 * @return boolean 返回IP是否在列表中
	 */
	public static function checkIp($ip) {
		if (!is_string($ip) || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
			return false;
		}

		$ips = [$ip];

		$arr = explode('.', $ip);
		$arr[3] = '*';
		$ips[] = implode('.',$arr);

		$arr[2] = '*';
		$ips[] = implode('.',$arr);

		return static::find()->where(['in', 'ip', $ips])->exists();
	}

	public static function findById($id)
	{
		return static::find()->where(['id' => $id])->one();
	}

	public static function existsByIp($ip, $excludeId = null)
	{
		$query = static::find()->where(['ip' => $ip]);
		$query->andFilterWhere(['<>', 'id', $excludeId]);
		return $query->exists();
	}
}
