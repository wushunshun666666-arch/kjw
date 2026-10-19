<?php
/**
 * 产品查询日志
 * @author suxh
 */

namespace common\log;

use yii\helpers\VarDumper;

class ProductQueryLog extends AppLog
{
	public function log($attributions, $resultCount=null) {
		foreach ($attributions as $key=>$value) {
			if (empty($value)) {
				unset($attributions[$key]);
			}
		}
		$message = VarDumper::export($attributions);
		$message = str_replace(["\n", ' ', "\t"], '', $message);
		$message .= ' '.$resultCount;
		$this->info($message);
	}
}