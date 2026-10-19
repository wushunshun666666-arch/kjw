<?php
/**
 * Redis消息队列
 *
 * @author suxh
 */

namespace mylibs\yii\mqueue;

use yii\base\Exception;

class RedisMessageQueue extends MessageQueue
{
	public $redis = null;

	public function init() {
		parent::init();
		if(!extension_loaded('redis')) {
			throw new Exception('RedisMessageQueue requires PHP redis extension to be loaded.');
		}

		if ($this->redis) {
			$this->queue = \Yii::$app->{$this->redis};
		}
		else {
			$server = $this->getServer();
			$queue = new \Redis();

			if (!($queue->connect($server->host,$server->port))) {
				throw new Exception('Connect redis server false.');
			}

			$this->queue = $queue;
		}
	}

	public function getServer() {
		if ($this->server == null) {
			$this->server = new RedisMessageQueueServer(array('host'=>'localhost'));
		}

		return $this->server;
	}

	public function setServer($config) {
		$this->server = new RedisMessageQueueServer($config);
	}

	protected function pushHeadValue($key, $value) {
		return $this->queue->lPush($key, $value);
	}

	protected function pushTailValue($key, $value) {
		return $this->queue->rPush($key, $value);
	}

	protected function popHeadValue($key) {
		return $this->queue->lPop($key);
	}

	protected function popTailValue($key) {
		return $this->queue->rPop($key);
	}

	protected function generateUniqueKey($key) {
		return md5($this->keyPrefix.'queue'.$key);
	}

	private $server = null;
	private $queue = null;
}