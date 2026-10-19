<?php
/**
 * Job.php
 *
 * @author suxh
 */

namespace common\jobs;

abstract class Job extends \mylibs\yii\job\Job
{
	public function push() {
		\Yii::$app->jobQueue->push($this);
	}

	public function pushDealy() {
		\Yii::$app->delay->dealy(function($job) {
			$job->push();
		}, $this);
	}
}