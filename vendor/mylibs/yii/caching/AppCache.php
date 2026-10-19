<?php
/**
 * AppCache.php
 * @author suxh
 */

namespace mylibs\yii\caching;

class AppCache
{
	public function __construct() {
		$cache = $this->getCache();
		$keyPrefix = $this->getKeyPrefix();
		$this->cache = new AppCacheInternal($cache, $keyPrefix);
	}

	protected function getCache() {
		return \Yii::$app->cache;
	}

	protected function getKeyPrefix() {
		return get_called_class();
	}

	protected $cache = null;
}

class AppCacheInternal
{
	public function __construct($cache, $keyPrefix) {
		$this->cache = $cache;
		$this->keyPrefix = $keyPrefix;
	}

	public function get($key) {
		$key = $this->generateUniqueKey($key);
		return $this->cache->get($key);
	}

	public function set($key, $value, $expire=0) {
		$key = $this->generateUniqueKey($key);
		return $this->cache->set($key, $value, $expire);
	}

	public function add($key, $value, $expire=0) {
		$key = $this->generateUniqueKey($key);
		return $this->cache->add($key, $value, $expire);
	}

	public function delete($key) {
		$key = $this->generateUniqueKey($key);
		return $this->cache->delete($key);
	}

	private function generateUniqueKey($key) {
		return $this->keyPrefix.$key;
	}

	private $cache;
	private $keyPrefix;
}