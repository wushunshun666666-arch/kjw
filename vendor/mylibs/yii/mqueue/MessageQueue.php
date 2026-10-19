<?php
/** 
 * MessageQueue.php
 * @author suxh
 */

namespace mylibs\yii\mqueue;

use yii\base\Component;

abstract class MessageQueue extends Component
{
	public $keyPrefix = null;

	public function pushHead($key, $value) {
		$key = $this->buildKey($key);
		$data = serialize($value);
		return $this->pushHeadValue($key, $data);
	}
	
	public function pushTail($key, $value) {
		$key = $this->buildKey($key);
		$data = serialize($value);
		return $this->pushTailValue($key, $data);
	}
	
	public function popHead($key) {
		$key = $this->buildKey($key);
		$data = $this->popHeadValue($key);
		if ($data) {
			return unserialize($data);
		}
		else {
			return false;
		}
	}
	
	public function popTail($key) {
		$key = $this->buildKey($key);
		$data = $this->popTailValue($key);
		if ($data) {
			return unserialize($data);
		}
		else {
			return false;
		}
	}
	
	protected abstract function pushHeadValue($key, $value);	
	protected abstract function pushTailValue($key, $value);
	protected abstract function popHeadValue($key);
	protected abstract function popTailValue($key);

	protected function buildKey($key) {
		return md5($this->keyPrefix.$key);
	}
}