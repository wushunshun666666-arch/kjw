<?php
/**
 * Controller.php
 *
 * @author suxh
 */

namespace app\controllers;

use yii\helpers\Console;

class Controller extends \yii\console\Controller
{
	/**
	 * {@inheritDoc}
	 * @see \yii\base\Controller::beforeAction()
	 */
	public function beforeAction($action) {
		$id = $action->getUniqueId();
		$id = str_replace('/', '\\', $id);
		$this->category = 'app\\'.$id;

		return parent::beforeAction($action);
	}

	public function error($message) {
		$this->stdout($message."\n", Console::FG_RED);
		\Yii::error($message, $this->category);
		return -1;
	}

	public function log($message) {
		//$this->stdout(date('Y-m-d H:i:s').' '.$message."\n");
		\Yii::info($message, $this->category);
		\Yii::getLogger()->flush(true);
	}

	private $category;
}