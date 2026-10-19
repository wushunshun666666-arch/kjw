<?php
/**
 * 支付日志记录
 *
 * @author suxh
 */

namespace mylibs\yii\payment;

class Logger extends \mylibs\payment\Logger
{
	/**
	 * __construct
	 *
	 * @param string $category
	 */
	public function __construct($category) {
		$this->category = $category;
	}

	/**
	 * 记录日志
	 *
	 * @param string $message
	 */
	protected function doLog($message, $type = 'NOTICE') {
		\Yii::info($message, $this->category);
	}

	/**
	 * @var string $category
	 */
	protected $category;
}
