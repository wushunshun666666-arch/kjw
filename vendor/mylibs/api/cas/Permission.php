<?php
/**
 * 模块权限业务模型
 * @author suxh
 */

namespace mylibs\api\cas;

use yii\base\UserException;

class Permission
{
	public function __construct($userId, $appId) {
		$this->userId = $userId;
		$this->appId = $appId;
		$this->privilegeClient = new PrivilegeClient();
	}

	public function isAdmin() {
		return ($this->userId == 101);
	}

	public function getModules() {
		if ($this->isAdmin()) {
			return [];
		}
		$privilege = $this->privilegeClient;
		$modules = $privilege->getRoutesOfUser($this->userId, $this->appId);
		if ($modules === false) {
			throw new UserException('获取权限失败：'.$privilege->getError());
		}

		return $modules;
	}

	/**
	 * 判断用户是否有指定模块的操作权限
	 * @param string $route 模块路由
	 * @param boolean 返回是否有权限
	 */
	public function hasPrivilege($route) {
		if ($this->isAdmin()) {
			return true;
		}
		$privilege = $this->privilegeClient;

		return $privilege->hasPrivilege($this->userId, $this->appId, $route);
	}

	/**
	 * @var integer
	 */
	private $userId;
	private $privilegeClient;
	private $appId;
}
