<?php
/**
 * Logger.php
 * @author suxh
 */

namespace mylibs\yii\job;

use yii\helpers\Json;

class Logger
{
	public function info($job, $message) {
		$message = $this->formatLog($job, $message);
		\Yii::info($message, 'app/job');
		\Yii::getLogger()->flush(true);
	}

	public function warning($job, $message) {
		$message = $this->formatLog($job, $message);
		\Yii::warning($message, 'app/job');
		\Yii::getLogger()->flush(true);
	}

	private function formatLog($job, $message) {
		$jobName = $job->className();
		$attributes = $job->getAttributes();
		$id = $attributes['id'];
		$createTime = $attributes['createTime'];
		unset($attributes['id']);
		unset($attributes['createTime']);
		$attributes = Json::encode($attributes);

		return "[{$jobName}][{$id}][{$createTime}] {$attributes} {$message}";
	}
}