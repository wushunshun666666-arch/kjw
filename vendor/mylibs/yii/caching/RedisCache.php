<?php
/**
 * RedisCache.php
 * @author suxh
 */

namespace mylibs\yii\caching;

use yii\base\Exception;

class RedisCache extends \yii\caching\Cache
{
	public $redis = null;

	/**
     * @inheritdoc
     */
	public function init() {
		parent::init();
		if(!extension_loaded('redis')) {
			throw new Exception('RedisCache requires PHP redis extension to be loaded.');
		}

		if ($this->redis) {
			$this->cache = \Yii::$app->{$this->redis};
		}
		else {
			$server = $this->getServer();
			$cache = new \Redis();

			if (!($cache->connect($server->host, $server->port))) {
				throw new Exception('Connect redis server false.');
			}

			$this->cache = $cache;
		}
	}

	/**
	 * @return RedisCacheServer
	 */
	public function getServer() {
		if ($this->server == null) {
			$this->server = new RedisCacheServer(array('host'=>'localhost'));
		}

		return $this->server;
	}

	/**
	 * 设置Redis服务器
	 *
	 * @param array $config 包括host, port
	 */
	public function setServer($config) {
		$this->server = new RedisCacheServer($config);
	}

	/**
     * @inheritdoc
     */
	protected function getValue($key) {
		return $this->cache->get($key);
	}

	/**
     * @inheritdoc
     */
	protected function setValue($key,$value,$expire) {
		if ($expire) {
			return $this->cache->setex($key,$expire,$value);
		}
		else {
			return $this->cache->set($key,$value);
		}
	}

	/**
     * @inheritdoc
     */
	protected function addValue($key,$value,$expire) {
		return $this->cache->setnx($key,$value);
	}

	/**
     * @inheritdoc
     */
	protected function deleteValue($key) {
		return (bool)$this->cache->delete($key);
	}

	/**
     * @inheritdoc
     */
	protected function flushValues() {
		return $this->cache->flushDB();
	}

	/**
	 * @var RedisCacheServer 默认为null
	 */
	private $server = null;
	/**
	 * @var Redis 默认为null, 参见php module redis;
	 */
	private $cache = null;
}