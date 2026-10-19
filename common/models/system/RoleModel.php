<?php
/**
 * 角色业务模型
 * @author suxh
 */

namespace common\models\system;

use common\models\LogicModel;
use common\models\Error;

class RoleModel extends LogicModel
{
	/**
	 * 构造函数
	 * @param Role $model 角色AR对象
	 */
	public function __construct($model) {
		$this->model = $model;
	}

	/**
	 * @return Role 返回角色数据
	 */
	public function getData() {
		return $this->model;
	}

	/**
	 * 根据ID获取角色对象
	 * @param integer $id 角色ID
	 * @return null|\common\models\system\RoleModel 存在返回角色对象，否则返回NULL
	 */
	public static function getById($id) {
		$model = Role::findById($id);
		if ($model === null) {
			return null;
		}

		return new self($model);
	}

	/**
	 * 检查角色名称是否可用
	 * @param string $name 角色名称
	 * @param integer $excludeId 例外ID
	 * @return \common\models\Error|boolean
	 */
	public static function checkName($name, $excludeId=null) {
		if (Role::existsByName($name, $excludeId)) {
			return new Error('角色名称已经存在。');
		}

		return true;
	}

	/**
	 * 创建角色
	 * @param string $name 角色名称
	 * @param string $description 角色描述
	 * @return \common\models\Error|\common\models\system\RoleModel
	 */
	public static function create($name, $description) {
		$res = self::checkName($name);
		if (Error::isError($res)) {
			return $res;
		}

		$model = new Role();
		$model->name 		= $name;
		$model->description = $description;
		if (!$model->save()) {
			return new Error($model->getErrors());
		}

		return new self($model);
	}

	/**
	 * 修改角色
	 * @param string $name 角色名称
	 * @param string $description 角色描述
	 * @return boolean 返回操作是否成功
	 */
	public function modify($name, $description) {
		$model = $this->model;
		$res = self::checkName($name, $model->id);
		if (Error::isError($res)) {
			return $this->addErrors($res->getErrors());
		}

		$model->name = $name;
		$model->description = $description;

		if (!$model->update(true, ['name', 'description', 'update_time'])) {
			return $this->addErrors($model->getErrors());
		}

		return true;
	}

	/**
	 * @return array 返回以树形方式组织的模块类别和模块信息
	 * 	id integer 类别ID
	 * 	name string 类别名称
	 * 	children array 子类别
	 * 	leaf array 叶子节点，保存类别下的模块信息
	 * 		id integer 模块ID
	 * 		name string 模块名称
	 * 		checked boolean 角色是否拥有该模块权限
	 */
	public function getModuleTree() {
		$model = $this->model;
		$myModules = RoleModule::getIdsOfRole($model->id);
		$modules = Module::getAllNamesGroupByCategory();
		foreach ($modules as &$items) {
			foreach ($items as &$item) {
				if (in_array($item['id'], $myModules)) {
					$item['checked'] = true;
				}
				else {
					$item['checked'] = false;
				}
			}
		}

		$categories = ModuleCategory::getTree(function($item) use ($modules) {
			if (isset($modules[$item->id])) {
				$leaf = $modules[$item->id];
			}
			else {
				$leaf = null;
			}
			return [
				'id' => $item->id,
				'name' => $item->name,
				'leaf' => $leaf,
			];
		});

		return $categories;
	}

	/**
	 * 设置模块权限
	 * @param array $newModules 新的模块ID列表
	 * @return boolean 返回操作是否成功
	 */
	public function setPermission($newModules) {
		$model = $this->model;
		$oldModules = RoleModule::getIdsOfRole($model->id);

		$add = array_diff($newModules, $oldModules);
		$del = array_diff($oldModules, $newModules);

		if (!empty($add) && !RoleModule::batchInsert($model->id, $add)) {
			return $this->setError('添加模块权限失败');
		}
		if (!empty($del) && !RoleModule::batchDelete($model->id, $del)) {
			return $this->setError('删除模块权限失败');
		}

		return true;
	}

	/**
	 * @return boolean 返回删除是否成功
	 */
	public function delete() {
		$model = $this->model;
		if (RoleModule::existsByRoleId($model->id)) {
			return $this->setError('角色已分配了模块权限，不能删除');
		}
		if (UserRole::existsByRoleId($model->id)) {
			return $this->setError('已有用户分配了该角色的权限，不能删除');
		}
		if (!$model->delete()) {
			return $this->addErrors('删除角色失败');
		}
		return true;
	}

	/**
	 * 删除角色用户
	 * @param type $userId
	 * @return boolean
	 */
	public function deleteUser($userId) {
		$model = $this->getData();
		$user = UserRole::find()->where(['role_id' => $model->id, 'user_id' => $userId])->one();
		if (!$user) {
			return $this->addErrors('角色用户不存在。');
		}
		if (!$user->delete()) {
			return $this->addErrors('删除角色用户失败。');
		}
		return true;
	}

	private $model;
}