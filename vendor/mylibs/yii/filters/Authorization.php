<?php
/**
 * 模块权限过滤器
 *
 * 检查用户是否拥有操作当前action的权限，禁止未授权的操作。
 * @author suxh
 */

namespace mylibs\yii\filters;

use yii\base\ActionFilter;
use yii\web\HttpException;

class Authorization extends ActionFilter
{
	public $permission = null;
	public $permissionClass = null;
	public $permissionOptions = [];
	/**
	 * @var array 排除清单，清单内的action不做权限验证
	 */
	public $exclude = [];

	/**
	 * @inheritdoc
	 */
	public function beforeAction($action) {
		if (in_array($action->id, $this->exclude)) {
			return true;
		}

		if (\Yii::$app->user->getIsGuest()) {
			return true;
		}

		$controller = $action->controller->id;
		$action     = $action->id;
		$route		= $controller.'/'.$action;

		$permission = $this->getPermission();
		if (!$permission->hasPrivilege($route)) {
			$this->log($route);
			throw new HttpException(403, '您没有权限操作该模块！');
		}

		return true;
	}

	private function getPermission() {
		if (!$this->permission && $this->permissionClass) {
			$identity = \Yii::$app->user->getIdentity();
			$this->permission = new $this->permissionClass($identity, $this->permissionOptions);
		}

		return $this->permission;
	}

	private function log($message) {
		\Yii::info($message, 'app\authorization');
	}
}