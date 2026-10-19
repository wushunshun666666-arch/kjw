<?php
/**
 * JobController.php
 *
 * php index.php job/listen
 * php index.php job/work
 *
 * @author suxh
 */

namespace app\controllers;

use app\controllers\Controller;
use mylibs\yii\job\Worker;

class JobController extends Controller
{
	public function actionListen() {
		$worker = new Worker(\Yii::$app->jobQueue);
		$worker->setExceptionHandler(function($job, $e) {
			if ($e instanceof \yii\db\Exception) {
				\Yii::$app->db->close();
			}
		});
		$worker->setWillSleepHandler(function() {
			\Yii::$app->db->close();
		});

		$worker->run();
	}

	public function actionWork() {
		$worker = new Worker(\Yii::$app->jobQueue);
		$worker->one();
	}

	/*public function actionTest() {
		$job = new \common\jobs\DistributionLogisticsCountJob();
		$job->distributionId = 9;
		$job->logisticsId = 6;
		for ($i = 0; $i < 50; ++$i) {
			$job->push();
		}
	}*/
}