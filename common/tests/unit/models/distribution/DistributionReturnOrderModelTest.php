<?php

namespace tests\unit\models\distribution;

use Codeception\Specify;
use common\models\Error;
use common\models\LogicModel;
use common\models\distribution\order\DistributionReturnOrderModel;

class DistributionReturnOrderModelTest extends \Codeception\Test\Unit
{
	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testCreate() {
		$distributionId = 9;
		$products = [
			17 => 1,
			353 => 1,
		];

		$order = DistributionReturnOrderModel::create($distributionId, $products);
		if (Error::isError($order)) {
			$this->fail($order->getError());
		}
	}

	public function testCancel() {
		$number = '31160728111715846197';
		$order = DistributionReturnOrderModel::getByNumber($number);
		if (!$order) {
			$this->fail('订单不存在');
		}
		if (!$order->cancel('客户取消')) {
			$this->fail($order->getError());
		}
	}

	public function testAccept() {
		$number = '31160728153051296152';
		$order = DistributionReturnOrderModel::getByNumber($number);
		if (!$order) {
			$this->fail('订单不存在');
		}
		if (!$order->accept()) {
			$this->fail($order->getError());
		}
	}

	public function testRefund() {
		$number = '31160728153051296152';
		$amount = 84 * 0.7;
		$order = DistributionReturnOrderModel::getByNumber($number);
		if (!$order) {
			$this->fail('订单不存在');
		}
		if (!$order->refund($amount)) {
			$this->fail($order->getError());
		}
	}
}