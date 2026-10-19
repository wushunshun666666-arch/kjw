<?php

namespace common\models\system;

use Yii;


/**
 * This is the model class for table "user".
 *
 * @property integer $id 用户ID
 * @property string $username 用户名
 * @property string $password_hash 登录密码
 * @property string $name 姓名
 * @property integer $department_id 所属部门ID
 * @property string $tel 固定电话
 * @property string $mobi 手机号
 * @property string $email Email
 * @property integer $status 状态
 * @property integer $timeout 登录超时时间
 * @property string $note 备注
 * @property string $create_time 添加时间
 * @property string $update_time 更新时间
 */
class User extends \common\models\ActiveRecord
{
    const ADMIN_ID = 101;

	/**
	 * @var integer 状态-有效
	 */
	const STATUS_1000 = 1000;
    /**
     * @var integer 状态-未激活
     */
    const STATUS_1001 = 1001;
	/**
	 * @var integer 状态-冻结
	 */
	const STATUS_1100 = 1100;
	/**
	 * @var integer 状态-不可登录
	 */
	const STATUS_8000 = 8000;
	/**
	 * @var integer 状态-已注销
	 */
	const STATUS_9000 = 9000;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return 'user';
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['username', 'password_hash', 'name', 'email', 'status', 'timeout'], 'required'],
            [['department_id', 'status', 'timeout'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['username'], 'string', 'max' => 20],
            [['password_hash'], 'string', 'max' => 60],
            [['name'], 'string', 'max' => 20],
            [['tel', 'mobi'], 'string', 'max' => 20],
            [['email'], 'string', 'max' => 90],
            [['note'], 'string', 'max' => 255],
            [['username'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id' => '用户ID',
            'username' => '用户名',
            'password_hash' => '登录密码',
            'name' => '姓名',
            'department_id' => '所属部门ID',
            'tel' => '固定电话',
            'mobi' => '手机号码',
            'email' => 'Email',
            'status' => '状态',
            'timeout' => '登录超时时间',
            'note' => '备注',
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
    public function getDepartment() {
        return $this->hasOne(Department::className(), ['id' => 'department_id']);
    }

    public function validatePassword($password) {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    public function setPassword($password) {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function modifyPassword($password) {
        $this->password = $password;
        if (!$this->update(true, ['password_hash', 'update_time'])) {
            return false;
        }

        return true;
    }

    public function updateStatus($newStatus, $oldStatus=null) {
    	$attributes = [
    		'status'=>$newStatus,
    		'update_time'=>$this->expressionNow()
    	];
    	$condition = ['id'=>$this->id];

    	if (is_array($oldStatus)) {
    		$condition = ['and', $condition, ['in', 'status', $oldStatus]];
    	}
    	else if ($oldStatus) {
    		$condition = ['and', $condition, ['status'=>$oldStatus]];
    	}
    	if ($this->updateAll($attributes, $condition) <= 0) {
    		return false;
    	}
    	$this->status = $newStatus;
    	return true;
    }

    /**
     * 根据用户名查找用户
     * @param string $username
     * @return User|null
     */
    public static function findByUsername($username) {
        return static::find()->where(['username' => $username])
        ->andWhere(['!=', 'status', self::STATUS_9000])->one();
    }

    public static function findById($id) {
    	return static::find()->where(['id' => $id])
    	->andWhere(['!=', 'status', self::STATUS_9000])->one();
    }

    public static function getAllNames() {
        $query = static::find();
        $query->where(['!=', 'status', self::STATUS_9000]);
        $query->orderBy('CONVERT(name using gbk)  ASC');
        $items = $query->all();

        $names = [];
        foreach ($items as $item) {
            $names[$item->id] = $item->name;
        }
        return $names;
    }

    /**
     * 查找用户名是否已经存在
     * @param string $username
     * @return boolean 返回用户名是否存在
     */
    public static function existsByUserName($username) {
    	return static::find()->where(['username' => $username])->exists();
    }

    /**
     * @return array 返回所有状态名称
     */
    public static function getStatusNames() {
    	return [
			self::STATUS_1000 => '有效',
            self::STATUS_1001 => '未激活',
			self::STATUS_1100 => '冻结',
			self::STATUS_8000 => '不可登录',
    		self::STATUS_9000 => '已注销',
    	];
    }

    /**
     * @return boolean 返回状态名称
     */
    public function getStatusName() {
    	$names = $this->getStatusNames();
    	return (isset($names[$this->status]))? $names[$this->status]:'未知';
    }

    /**
     * @return boolean 返回是否冻结状态
     */
    public function isFreeze() {
        return ($this->status == self::STATUS_1100);
    }

    /**
     * @return boolean 返回是否不可登录状态
     */
    public function isNoLogin() {
        return ($this->status == self::STATUS_8000);
    }

    /**
     * @return boolean 返回状态是否已注销
     */
    public function isClose() {
    	return ($this->status == self::STATUS_9000);
    }

    /**
     * @return boolean 返回是否是超级管理员
     */
    public function isAdmin() {
    	return ($this->id == self::ADMIN_ID);
    }

    public function getReadableName() {
    	return "{$this->username}-{$this->name}";
    }
}
