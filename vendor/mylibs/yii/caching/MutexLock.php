<?php
/**
 * MutexLock.php
 * @author suxh
 */

namespace mylibs\yii\caching;

class MutexLock
{
	public function __construct($key, $redis = null) {
		if (!$redis) {
			$redis = \Yii::$app->redis;
		}
		$this->key = $key;
		$this->redis = $redis;
	}

	public function __destruct() {
		if ($this->locked) {
			$this->unlock();
		}
	}

	public function lock($expire) {
		$res = $this->redis->setnx($this->key, time()+$expire);
		if ($res) {
			$this->redis->expire($this->key, $expire);
			$this->locked = true;
		}
		else {
			$time = $this->redis->get($this->key);
			if ($time < time()) {
				$this->redis->delete($this->key);
			}
		}
		return $res;
	}

	public function unlock() {
		$this->redis->delete($this->key);
	}

	protected $locked = false;
	protected $key = null;
	protected $redis = null;
}
