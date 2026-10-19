<?php
/**
 * UserModel.php
 *
 * @author suxh
 */

namespace common\models\system;

use common\models\LogicModel;
use common\models\Error;
use mylibs\RandString;
use mylibs\yii\db\Transaction;

class UserModel extends LogicModel
{
	/**
	 * @event Event 新建用户
	 */
	const EVENT_AFTER_CREATE = 'afterCreate';
	/**
	 * @event Event 编辑用户
	 */
	const EVENT_AFTER_MODIFY = 'afterModify';

	public function __construct($model) {
		$this->model = $model;
	}

	public function behaviors() {
		return [
			[
				'class' => UserCacheClearBehavior::className(),
			],
		];
	}

	/**
	 * @return User 返回用户对象
	 */
	public function getData() {
		return $this->model;
	}

	public static function getById($id) {
		$model = User::findById($id);
		if ($model === null) {
			return null;
		}

		return new self($model);
	}

	public static function getByUsername($username) {
		$model = User::findByUsername($username);
		if ($model === null) {
			return null;
		}

		return new self($model);
	}

	public static function checkUserName($username) {
		$username = strtolower($username);
		if (User::existsByUserName($username)) {
			return new Error('用户名已经存在。');
		}

		return true;
	}

	public static function create($attributes) {
		$attributes['username'] = strtolower($attributes['username']);
		$res = self::checkUserName($attributes['username']);
		if (Error::isError($res)) {
			return $res;
		}

		$model = new User();
		$model->username		= $attributes['username'];
		$model->password 		= $attributes['password'];
		$model->name 			= $attributes['name'];
		$model->department_id 	= $attributes['department_id'];
		$model->tel 			= $attributes['tel'];
		$model->mobi 			= $attributes['mobi'];
		$model->email 			= $attributes['email'];
		$model->status			= User::STATUS_1000;//TODO
		$model->note			= $attributes['note'];
		$model->timeout			= 1800;
		if (!$model->save()) {
			return new Error($model->getErrors());
		}

		$model = new self($model);
		$model->afterCreate();

		return $model;
	}

	public function afterCreate() {
		$this->trigger(self::EVENT_AFTER_CREATE);
	}

	public static function generatePassword() {
		return RandString::complex(8);
	}

	public function modify($attributes) {
		$model = $this->model;
		if (isset($attributes['name'])) {
			$model->name = $attributes['name'];
		}
		if (isset($attributes['department_id'])) {
			$model->department_id = $attributes['department_id'];
		}

		$model->tel 	= $attributes['tel'];
		$model->mobi 	= $attributes['mobi'];
		$model->email 	= $attributes['email'];
		$model->note	= $attributes['note'];
		if (!$model->save()) {
			return $this->addErrors($model->getErrors());
		}

		$this->afterModify();

		return true;
	}

	public function afterModify() {
		$this->trigger(self::EVENT_AFTER_MODIFY);
	}

	public function validatePassword($password) {
		$model = $this->model;
		return $model->validatePassword($password);
	}

	public function modifyPassword($password) {
		$model = $this->model;
		if (!$model->modifyPassword($password)) {
			return $this->addErrors($model->getErrors());
		}

		return true;
	}

	/**
	 * 注销
	 * @return boolean 返回操作是否成功
	 */
	public function close() {
		$model = $this->model;

		$transaction = new Transaction();
		// 重置密码
		$password = $this->generatePassword();
		if (!$this->modifyPassword($password)) {
			$transaction->rollback();
			return false;
		}
		// 更新状态为已注销
		if (!$this->updateStatus(User::STATUS_9000, $model->status)) {
			$transaction->rollback();
			return false;
		}

		$transaction->commit();

		return true;
	}

	public function getRoleIds() {
		$model = $this->model;
		return UserRole::getRoleIdsOfUser($model->id);
	}

	public function setPermission($newRoles) {
		$oldRoles = $this->getRoleIds();

		$add = array_diff($newRoles, $oldRoles);
		$del = array_diff($oldRoles, $newRoles);

		$model = $this->model;
		if (!empty($add) && !UserRole::batchInsert($model->id, $add)) {
			return $this->setError('添加角色权限失败');
		}
		if (!empty($del) && !UserRole::batchDelete($model->id, $del)) {
			return $this->setError('删除角色权限失败');
		}

		return true;
	}

	/**
	 * @return array 返回用户的完整联系信息
	 * 	name string 名称
	 * 	contactName string 联系人
	 * 	contactTel string 固定电话
	 * 	contactMobi string 手机号码
	 * 	contactEmail string email
	 * 	provinceId integer 省份
	 * 	cityId integer 城市
	 * 	districtId integer 区县
	 * 	address string 地址
	 */
	public function getFullContact() {
		$model = $this->model;
		//TODO
		return [
			'name' 			=> \Yii::$app->params['companyName'],
			'contactName' 	=> $model->name,
			'contactTel'	=> $model->tel,
			'contactMobi'	=> $model->mobi,
			'contactEmail'	=> $model->email,
			'provinceId'	=> 8440000,
			'cityId'		=> 8440100,
			'districtId'	=> 8440111,
			'address'		=> '广东省广州市白云区均禾大道子泓大厦',
		];
	}

	private function updateStatus($newStatus, $oldStatus=null, $note='') {
		$model = $this->model;
		if (!$model->updateStatus($newStatus, $oldStatus)) {
			return $this->setError('更新用户状态失败');
		}
		return true;
	}

	private $model;
}