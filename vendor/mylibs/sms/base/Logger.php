<?php
/**
 * Logger.php
 *
 * @author James
 */

namespace mylibs\sms\base;

class Logger
{
	public function __construct($category) {
		$this->category = $category;
	}

	public function log($message, $level='DEBUG') {
		\Yii::info($message, $this->category);
	}

	private $category;
}