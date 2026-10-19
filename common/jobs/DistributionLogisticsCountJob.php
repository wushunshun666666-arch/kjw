<?php
/**
 * DistributionLogisticsCountJob.php
 *
 * @author suxh
 */

namespace common\jobs;

use common\models\Error;
use common\models\distribution\order\logistics\DistributionLogistics;

class DistributionLogisticsCountJob extends Job
{
	public $distributionId;
	public $logisticsId;

	public function handle() {
		if ($this->logisticsId > 0) {
			$res = DistributionLogistics::setLogistics($this->distributionId, $this->logisticsId);
			if (Error::isError($res)) {
				return $this->setError($res->getError());
			}
		}

		return true;
	}
}