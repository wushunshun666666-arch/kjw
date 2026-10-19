<?php
/**
 * UserService.php
 * @author suxh
 */

namespace app\models\system;

use app\models\Service;
use common\caching\UserCache;
use common\models\Error;
use common\models\system\User;
use common\models\system\UserModel;
use common\models\system\Department;
use common\models\system\DepartmentModel;
use common\models\system\Role;
use common\mail\UserRegisterMailer;
use common\mail\UserResetPasswordMailer;
use yii\data\ActiveDataProvider;

class UserService extends Service
{
	public function getDepartments() {
		return DepartmentModel::getTreeList();
	}

	public function getDepartmentTree() {
		return DepartmentModel::getTree();
	}

	public function getStatusNames() {
		return User::getStatusNames();
	}

	public function search(UserSearchForm $form) {
		$query = User::find()->joinWith('department');
		$query->andFilterWhere(['user.id' => $form->id]);
		$query->andFilterWhere(['like', 'user.username', $form->username]);
		$query->andFilterWhere(['like', 'user.name', $form->name]);
		$query->andFilterWhere(['user.status' => $form->status]);

		if (!empty($form->departmentId)) {
			$childIds = Department::getChildIds($form->departmentId);
			$query->andWhere(['in', 'department.id', $childIds]);
		}

		return new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'pageSize'=>$this->getOption('pagesize'),
			],
			'sort' => [
				'attributes' => [
					'user.username',
					'user.create_time' => [
						'asc' => ['user.id' => SORT_ASC],
						'desc' => ['user.id' => SORT_DESC],
					],
					'user.name' => [
						'asc' => ['CONVERT(user.name using gbk)' => SORT_ASC],
						'desc' => ['CONVERT(user.name using gbk)' => SORT_DESC],
					],
				],
				'defaultOrder'=>['user.create_time' => SORT_DESC],
			],
		]);
	}

	/**
	 * 取得用户详细信息
	 * @param integer $id 用户ID
	 * @return User|false 返回用户对象
	 */
	public function getDetail($id) {
		$user = $this->getModel($id);
		if (!$user) {
			return false;
		}

		return $user->getData();
	}

	public function add(UserAddForm $form) {
		$attributes = $form->getSafeAttributes();
		$attributes['password'] = UserModel::generatePassword();

		$user = UserModel::create($attributes);
		if (Error::isError($user)) {
			return $this->addErrors($user->getErrors());
		}

		// 发送注册成功通知信
		$mailer = new UserRegisterMailer();
		$mailer->to 	  = $attributes['email'];
		$mailer->username = $attributes['username'];
		$mailer->password = $attributes['password'];
		$mailer->send();

		return true;
	}

	public function modify($id, UserModifyForm $form) {
		$user = $this->getModel($id);
		if (!$user) {
			return false;
		}

		$attributes = $form->getSafeAttributes();

		if (!$user->modify($attributes)) {
			return $this->addErrors($user->getErrors());
		}

		return true;
	}

	public function resetPassword($id) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}

		$newPassword = UserModel::generatePassword(false);
		if (!$model->modifyPassword($newPassword)) {
			return $this->addErrors($model->getErrors());
		}

		/*// 发送重置密码通知信
		$data = $model->getData();
		$mailer = new UserResetPasswordMailer();
		$mailer->to 	  = $data->email;
		$mailer->password = $newPassword;
		$mailer->send();*/

		return $newPassword;
	}

	/**
	 * 注销用户
	 * @param integer $id 用户ID
	 * @return boolean 返回操作是否成功
	 */
	public function close($id) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}

		if (!$model->close()) {
			return $this->addErrors($model->getErrors());
		}

		return true;
	}

	public function getPermission($id) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}

		$allRoles = Role::getAllNames();
		$roleIds = $model->getRoleIds();
		$roles = [];

		foreach ($allRoles as $id=>$name) {
			$selected = in_array($id, $roleIds);
			$roles[] = [
				'id' => $id,
				'name' => $name,
				'selected' => $selected,
			];
		}

		return [
			'model' => $model->getData(),
			'roles' => $roles,
		];
	}

	public function setPermission($id, $roles) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}

		if (!$model->setPermission($roles)) {
			return $this->addErrors($model->getErrors());
		}

		return true;
	}

	public function getUsers() {
		$cache = new UserCache();
		return $cache->get();
	}

	/**
	 * 根据ID获取用户模型对象
	 * @param integer $id 用户ID
	 * @return UserModel|false 返回用户模型
	 */
	private function getModel($id) {
		$user = UserModel::getById($id);
		if (!$user) {
			return $this->setError('用户不存在');
		}

		return $user;
	}
}