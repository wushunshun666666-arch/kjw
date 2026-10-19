<?php
/**
 * RoleService.php
 * @author suxh
 */

namespace app\models\system;

use app\models\Service;
use common\models\Error;
use common\models\system\Role;
use common\models\system\RoleModel;
use common\models\system\UserRole;
use yii\data\ActiveDataProvider;

/*
namespace app\models\system;

use app\models\Service;
use common\models\Error;
use common\models\system\Role;
use common\models\system\RoleModel;
use common\models\system\UserRole;
use common\models\system\User;
use yii\data\ActiveDataProvider;

*/
class RoleService extends Service
{
	public function search(RoleSearchForm $form) {
		$query = Role::find();
		$query->andFilterWhere(['like', 'name', $form->name]);
		return new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'pageSize'=>$this->getOption('pagesize'),
			],
			'sort' => [
				'attributes' => [
					'name' => [
						'asc' => ['CONVERT(name using gbk)' => SORT_ASC],
						'desc' => ['CONVERT(name using gbk)' => SORT_DESC],
					],
				],
				'defaultOrder'=>['name' => SORT_ASC],
			],
		]);
	}

	/**
	 * 取得角色详细信息
	 * @param integer $id 角色ID
	 * @return Role|false 返回角色对象
	 */
	public function getDetail($id) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}

		return $model->getData();
	}

	public function add(RoleAddForm $form) {
		$model = RoleModel::create($form->name, $form->description);
		if (Error::isError($model)) {
			return $this->addErrors($model->getErrors());
		}
		return true;
	}

	public function modify($id, RoleModifyForm $form) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}
		if (!$model->modify($form->name, $form->description)) {
			return $this->addErrors($model->getErrors());
		}
		return true;
	}

	/**
	 * 删除当前角色下的，单个用户
	 * @param integer $id
	 * @param integer $userId
	 * @return boolean
	 */
	public function deleteUser($id, $userId){
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}
		if (!$model->deleteUser($userId)) {
			return $this->setError($model->getError());
		}
		return true;
	}

	/**
	 * 删除角色
	 * @param integer $id
	 * @return boolean
	 */
	public function deleteRole($id){
		$model = $this->getModel($id);
		if(!$model){
			return false;
		}

		if (!$model->delete($id)) {
			return $this->setError($model->getError());
		}
		return true;
	}

	public function getPermission($id) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}
		return [
			'model' => $model->getData(),
			'moduleTree' => $model->getModuleTree(),
		];
	}

	public function setPermission($id, $modules) {
		$model = $this->getModel($id);
		if (!$model) {
			return false;
		}

		if (!$model->setPermission($modules)) {
			return $this->addErrors($model->getErrors());
		}

		return true;
	}

	/**
	 * 根据ID获取角色模型对象
	 * @param integer $id 角色ID
	 * @return RoleModel|false 返回角色模型
	 */
	private function getModel($id) {
		$model = RoleModel::getById($id);
		if (!$model) {
			return $this->setError('角色不存在');
		}

		return $model;
	}


	//根据角色模块id获取当前当前角色的所有用户
	public function getRoleUser($id){
		$query = UserRole::find()->joinWith('user');
		$query->where(['user_role.role_id'=>$id]);
		return new ActiveDataProvider([
			'query' => $query,
			'pagination' => [
				'pageSize'=>$this->getOption('pagesize'),
			],
			'sort' => [
				'attributes' => [
					'user.name' => [
						'asc' => ['CONVERT(user.name using gbk)' => SORT_ASC],
						'desc' => ['CONVERT(user.name using gbk)' => SORT_DESC],
					],
				],
				'defaultOrder'=>['user.name' => SORT_ASC],
			],
		]);
	}
}
