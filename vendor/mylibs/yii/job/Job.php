<?php
/**
 * Job.php
 * @author suxh
 */

namespace mylibs\yii\job;

abstract class Job extends \yii\base\BaseObject
{
	/**
	 * @var string 任务ID，任务的唯一标识
	 */
	public $id;

	/**
	 * @var string 任务创建时间，默认为当前时间
	 */
	public $createTime;

	public function init() {
		if (!$this->id) {
			$this->id = $this->generateId();
		}
		if (!$this->createTime) {
			$time = new \DateTime();
			$this->createTime = $time->format('Y-m-d H:i:s');
		}
	}

	public function attributes() {
		$class = new \ReflectionClass($this);
		$names = [];
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
			if (!$property->isStatic()) {
				$names[] = $property->getName();
			}
		}

		return $names;
	}

	public function getAttributes() {
		$values = [];
		$names = $this->attributes();

		foreach ($names as $name) {
			$values[$name] = $this->$name;
		}

		return $values;
	}

	public function getError() {
		return $this->error;
	}

	public function beforeHandle() {

	}

	public function afterHandle() {

	}

	public function failed($error) {

	}

	public abstract function handle();

	protected function setError($error) {
		$this->error = $error;
		return false;
	}

	protected function generateId() {
		return md5(uniqid('', true));
	}

	private $error;
}
