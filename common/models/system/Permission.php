<?php
/**
 * 模块权限业务模型
 * @author suxh
 */

namespace common\models\system;

use common\models\LogicModel;

class Permission extends LogicModel
{
	public function __construct($userId) {
		$this->userId = $userId;
	}

	public function isAdmin() {
		return ($this->userId == User::ADMIN_ID);
	}

	public function getModules() {
		if ($this->isAdmin()) {
			return [];
		}

		$results = [];
		$modules = Module::getUserRoleModules($this->userId);
		foreach ($modules as $module) {
			$results[] = $module['url'];
		}

		// array_unique唯一化后会将数字下标转为字串下标，array_values可以重建数字索引
		return array_values(array_unique($results));
	}

	/**
	 * 判断用户是否有指定模块的操作权限(注：管理员默认拥有所有权限)
	 * @param string $url 模块路由
	 * @param boolean 返回是否有权限
	 */
	public function hasPrivilege($url) {
		// 管理员，不进行权限判断
		if($this->isAdmin()) {
			return true;
		}

		$module = Module::getByUrl($url);
		if($module == null) {
			return $this->setError('模块不存在');
		}

		$moduleId = $module->id;

	    // 根据ID判断用户角色模块
	    if (Module::hasUserRoleModule($this->userId, $moduleId)) {
	    	return true;
	    }

	    return $this->setError("“{$module->name}”模块未授权");
	}

	private $userId = null;
}