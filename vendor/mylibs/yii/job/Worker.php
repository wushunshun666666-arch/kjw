<?php
/**
 * Worker.php
 * @author suxh
 */

namespace mylibs\yii\job;

use yii\helpers\Json;
class Worker
{
	public function __construct($queue, $logger = null) {
		$this->queue = $queue;
		if (!$logger) {
			$logger = new Logger();
		}
		$this->logger = $logger;
	}

	public function run($name = 'default') {
		while (!$this->isStop) {
			while ($job = $this->queue->pop($name)) {
				$this->executeJob($job);
			}
			$this->onWillSleep();
			sleep($this->sleep);
		}
	}

	public function one($name = 'default') {
		$job = $this->queue->pop($name);
		if ($job) {
			$this->executeJob($job);
		}
	}

	public function setExceptionHandler($exceptionHandler) {
		$this->exceptionHandler = $exceptionHandler;
	}

	public function setWillSleepHandler($willSleepHandler) {
		$this->willSleepHandler = $willSleepHandler;
	}

	protected function executeJob($job) {
		try {
			$job->beforeHandle();
			if (!$job->handle()) {
				$this->onJobFailed($job, $job->getError());
			}
			$job->afterHandle();
		} catch (\Exception $e) {
			$this->onJobFailed($job, $e->getMessage());
			$this->onJobException($job, $e);
		}

		$this->onJobSuccess($job);
	}

	protected function onJobSuccess($job) {
		$this->info($job, 'success');
	}

	protected function onJobFailed($job, $error) {
		$this->warning($job, $error);
		$job->failed($error);
	}

	protected function onJobException($job, $e) {
		if ($this->exceptionHandler) {
			call_user_func($this->exceptionHandler, $job, $e);
		}
	}

	protected function onWillSleep() {
		if ($this->willSleepHandler) {
			call_user_func($this->willSleepHandler);
		}
	}

	protected function info($job, $message) {
		$this->logger->info($job, $message);
	}

	protected function warning($job, $message) {
		$this->logger->warning($job, $message);
	}

	private function formatLog($job, $message) {
		$jobName = $job->className();
		$attributes = Json::encode($job->getAttributes());

		return "[{$jobName}] {$attributes} {$message}";
	}

	private $queue;
	private $isStop = false;
	private $sleep = 3;
	private $exceptionHandler = null;
	private $willSleepHandler = null;
}