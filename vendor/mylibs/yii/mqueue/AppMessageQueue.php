<?php
/**
 * AppQueue.php
 * @author suxh
 */

namespace mylibs\yii\mqueue;

class AppMessageQueue
{
	public function __construct() {
		$key = $this->getKey();
		$queue = $this->getQueue();
		$this->queue = new MessageQueueInternal($queue, $key);
	}
	
	protected function getKey() {
		return get_called_class();
	}
	
	protected function getQueue() {
		return \Yii::$app->mQueue;
	}

	/**
	 * @var MessageQueueInternal 消息队列对象
	 */
	protected $queue = null;
}

class MessageQueueInternal
{
	public function __construct($queue, $key) {
		$this->queue = $queue;
		$this->key = $key;
	}

	public function pushHead($value) {
		return $this->queue->pushHead($this->key, $value);
	}

	public function pushTail($value) {
		return $this->queue->pushTail($this->key, $value);
	}

	public function popHead() {
		return $this->queue->popHead($this->key);
	}

	public function popTail() {
		return $this->queue->popTail($this->key);
	}

	private $queue =null;
	private $key = null;
}