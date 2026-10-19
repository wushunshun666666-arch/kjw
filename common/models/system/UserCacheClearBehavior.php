<?php
/**
 * UserCacheClearBehavior.php
 *
 * @author James
 */

namespace common\models\system;

use yii\base\Behavior;
use common\caching\UserCache;
class UserCacheClearBehavior extends Behavior
{
	public function events() {
		return [
			UserModel::EVENT_AFTER_CREATE => 'clearUserCache',
			UserModel::EVENT_AFTER_MODIFY => 'clearUserCache',
		];
	}

	public function clearUserCache($event) {
		$cache = new UserCache();
		$this->dealy(function($cache) {
			$cache->delete();
		}, $cache);
	}

	private function dealy($handler, $data=null) {
		\Yii::$app->delay->dealy($handler, $data);
	}
}