<?php
/**
 * json-rpc调用日志记录
 *
 * @author suxh
 */

namespace mylibs\api;

class Logger extends \mylibs\jsonrpc\Logger
{
	/**
	 * __construct
	 *
	 * @param string $category
	 */
	public function __construct($category) {
		$this->category = $category;
	}

	protected function filterParams() {
		return [
			'password'
		];
	}

	/**
	 * 记录日志
	 * @param string $message
	 * @param string $type
	 */
	protected function doLog($message, $type = 'NOTICE') {
		$filterParams = $this->filterParams();
		foreach ($filterParams as $param) {
			$pattern = '/("'.$param.'":)"(.*)"/i';
			$message = preg_replace($pattern, '${1}"FILTERED"', $message);
		}

		\Yii::info($message, $this->category);
		\Yii::getLogger()->flush(true);
	}

	/**
	 * @var string $category
	 */
	protected $category;
}
