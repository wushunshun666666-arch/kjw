<?php
/**
 * 日志基类
 * @author suxh
 */

namespace common\log;

use Yii;
use yii\helpers\Inflector;

class AppLog
{
	public function __construct($category=null) {
		if ($category === null) {
			$category = get_called_class();
			$category = str_replace('Log', '', $category);
			$pos = strrpos($category, '\\');
			if ($pos !== false) {
				$category = substr($category, $pos+1);
			}
			$category = Inflector::camel2id($category);

			$category = 'app\\'.$category;
		}
		$this->category = $category;
	}

	public function info($message) {
		\Yii::info($message, $this->category);
	}

	private $category;
}