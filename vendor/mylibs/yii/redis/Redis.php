<?php
/**
 * Redis.php
 * @author suxh
 */

namespace mylibs\yii\redis;

use yii\base\Exception;

class Redis extends \yii\base\Component
{
	/**
	 * @var RedisServer|null
	 */
	private $server = null;

	/**
     * @inheritdoc
     */
	public function init() {
		parent::init();
		if(!extension_loaded('redis')) {
			throw new Exception('RedisCache requires PHP redis extension to be loaded.');
		}

		$this->redis = new \Redis();

		$server = $this->getServer();

		if (!($this->redis->connect($server->host, $server->port))) {
			throw new Exception('Connect redis server false.');
		}
	}

	public function get($key) {
		return $this->redis->get($key);
	}

	public function getMultiple($keys) {
		return $this->redis->mGet($keys);
	}

	public function setex($key,$expire,$value) {
		return $this->redis->setex($key,$expire,$value);
	}

	public function set($key,$value) {
		return $this->redis->set($key,$value);
	}

	public function setnx($key,$value) {
		return $this->redis->setnx($key,$value);
	}

	public function delete($key) {
		return $this->redis->del($key);
	}

	public function flushDB() {
		return $this->redis->flushDB();
	}


	public function lPush($key, $value) {
		return $this->redis->lPush($key, $value);
	}

	public function rPush($key, $value) {
		return $this->redis->rPush($key, $value);
	}

	public function rPop($key) {
		return $this->redis->rPop($key);
	}

	public function lPop($key) {
		return $this->redis->rPop($key);
	}

	public function zDelete($key, $member) {
		return $this->redis->zRem($key, $member);
	}

	public function zAdd($key, $score, $value) {
		return $this->redis->zAdd($key, $score, $value);
	}

	public function zRange($key, $start, $end, $withscores=false) {
		return $this->redis->zRange($key, $start, $end, $withscores);
	}

	public function zRangeByLex($key, $min, $max, $offset=0, $limit=10) {
		return $this->redis->zRangeByLex($key, $min, $max, $offset, $limit);
	}

	public function zScan($key, &$iterator, $pattern=null, $count=null) {
		return $this->redis->zScan($key, $iterator, $pattern, $count);
	}

	public function publish($channel, $message) {
		return $this->redis->publish($channel, $message);
	}

	public function expire($key, $seconds) {
		return $this->redis->expire($key, $seconds);
	}

	public function setOption($name, $value) {
		return $this->redis->setOption($name, $value);
	}

	/**
	 * @return RedisServer Redis服务器配置
	 */
	public function getServer() {
		if ($this->server == null) {
			$this->server = new RedisServer(array('host'=>'localhost'));
		}

		return $this->server;
	}

	/**
	 * 设置Redis服务器
	 *
	 * @param array $config 包括host, port
	 */
	public function setServer($config) {
		$this->server = new RedisServer($config);
	}

	/**
	 * @var \Redis
	 */
	private $redis = null;
}
