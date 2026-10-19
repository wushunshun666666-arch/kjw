<?php

namespace tests\unit\models\distribution;

use Codeception\Specify;
use common\models\Error;
use common\models\LogicModel;
use common\models\distribution\order\purchase\DistributionPurchasePreorderModel;

class DistributionPurchasePreorderModelTest extends \Codeception\Test\Unit
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

		$order = DistributionPurchasePreorderModel::create($distributionId, $products);
		if (Error::isError($order)) {
			$this->fail($order->getError());
		}
	}

	public function testCancel() {
		$number = '30170323135401339210';
		$order = DistributionPurchasePreorderModel::getByNumber($number);
		if (!$order) {
			$this->fail('订单不存在');
		}
		if (!$order->cancel('客户取消')) {
			$this->fail($order->getError());
		}
	}

	public function testAccept() {
		$number = '31160728153051296152';
		$order = DistributionPurchasePreorderModel::getByNumber($number);
		if (!$order) {
			$this->fail('订单不存在');
		}
		if (!$order->accept()) {
			$this->fail($order->getError());
		}
	}
}