<?php
/**
 * RedisMessageQueueServer.php
 *
 * @author suxh
 */

namespace mylibs\yii\mqueue;

use yii\base\Exception;

class RedisMessageQueueServer extends \yii\base\BaseObject
{
	/**
	 * @var string Redis服务器IP
	 */
	public $host;
	/**
	 * @var integer Redis端口，默认6379
	 */
	public $port=6379;

	/**
	 * Redis服务器
	 * @param array $config 包括host, port两项
	 * @throws Exception
	 */
	public function __construct($config) {
		if(is_array($config)) {
			foreach($config as $key=>$value) {
				$this->$key=$value;
			}
			if($this->host===null) {
				throw new Exception('RedisCache server configuration must have "host" value.');
			}
		}
		else {
			throw new Exception('RedisCache server configuration must be an array.');
		}
	}
}
