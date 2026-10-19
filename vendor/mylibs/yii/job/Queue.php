<?php
/**
 * Queue.php
 * @author suxh
 */

namespace mylibs\yii\job;

use yii\base\Component;

class Queue extends Component
{
	public $redis = null;
	public $keyPrefix = 'jobs';

	public function init() {
		parent::init();
		if(!extension_loaded('redis')) {
			throw new Exception('Queue requires PHP redis extension to be loaded.');
		}

		if (!$this->redis) {
			throw new Exception('redis can not be null.');
		}
		$this->queue = \Yii::$app->{$this->redis};
	}

	public function push($job, $name = 'default') {
		$data = [
			'class' 	=> $job->className(),
			'attributes'=> $job->getAttributes(),
		];
		return $this->pushValue($name, $data);
	}

	public function pop($name = 'default') {
		$data = $this->popValue($name);
		if (!$data) {
			return null;
		}

		$class = $data['class'];
		$attributes = $data['attributes'];
		$job = new $class($attributes);

		return $job;
	}

	protected function pushValue($key, $value) {
		$key = $this->generateUniqueKey($key);
		$value = serialize($value);
		return $this->queue->lPush($key, $value);
	}

	protected function popValue($key) {
		$key = $this->generateUniqueKey($key);
		$value = $this->queue->rPop($key);
		if ($value) {
			$value = unserialize($value);
		}
		return $value;
	}

	protected function generateUniqueKey($key) {
		return md5($this->keyPrefix.$key);
	}

	protected $queue = null;
}