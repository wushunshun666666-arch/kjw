<?php
/**
 * OnlyProcessFilter
 *
 * @author suxh
 */

namespace mylibs\proc;

use yii\base\ActionFilter;

class OnlyProcessFilter extends ActionFilter
{
	public $actions = [];

	/**
	 * @inheritdoc
	 */
	public function beforeAction($action) {
		if (!in_array($action->id, $this->actions)) {
			return true;
		}

		$id = $action->getUniqueId();
		$id = str_replace(['/', '\\', '::', ':'], '-', $id);
		$lockFile = \Yii::$app->getRuntimePath() . '/logs/app-'.$id.'.lock';

		$this->lock = new FileLock($lockFile);
		if (!$this->lock->lock()) {
			throw new \Exception('加锁失败，可能已有进程正在执行');
		}

		return true;
	}

	public function afterAction($action, $result) {
		if ($this->lock) {
			$this->lock->unlock();
		}
		return $result;
	}

	protected $lock = null;
}