<?php
namespace tests\unit\models\distribution;

use Codeception\Specify;
use common\models\Error;
use common\models\LogicModel;
use common\models\distribution\marketing\DistributionCreditLoanOrderModel;

class DistributionCreditLoanOrderModelTest extends \Codeception\Test\Unit
{
	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testCreate() {
		$attributes = [];
		$attributes['distributionId'] 	= 9;
		$attributes['activityId'] 		= 1002001;
		$attributes['amount'] 			= 10000;
		$attributes['expireDate'] 		= '2018-12-31';
		$attributes['note'] 			= '只是备注而已';

		$order = DistributionCreditLoanOrderModel::create($attributes);
		if (Error::isError($order)) {
			$this->fail($order->getError());
		}
	}

	public function testCancel() {
		$order = $this->create();
		if (Error::isError($order)) {
			$this->fail($order->getError());
		}

		$order = DistributionCreditLoanOrderModel::getById($order->getData()->id);
		if (!$order) {
			$this->fail('订单不存在');
		}
		if (!$order->cancel('只是备注')) {
			$this->fail($order->getError());
		}
	}

	public function testAudit() {
		$order = $this->create();
		if (Error::isError($order)) {
			$this->fail($order->getError());
		}

		$order = DistributionCreditLoanOrderModel::getById($order->getData()->id);
		if (!$order) {
			$this->fail('订单不存在');
		}
		if (!$order->audit('只是备注')) {
			$this->fail($order->getError());
		}
	}

	private function create() {
		$attributes = [];
		$attributes['distributionId'] 	= 9;
		$attributes['activityId'] 		= 1002001;
		$attributes['amount'] 			= 10000;
		$attributes['expireDate'] 		= '2018-12-31';
		$attributes['note'] 			= '只是备注而已';

		return DistributionCreditLoanOrderModel::create($attributes);
	}
}