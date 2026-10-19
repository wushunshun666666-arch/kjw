<?php

namespace tests\unit\models\distribution;

use Codeception\Specify;
use common\models\Error;
use common\models\LogicModel;
use common\models\distribution\allocation\DistributionAllocationOrderModel;

class DistributionAllocationOrderModelTest extends \Codeception\Test\Unit
{
	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testCreate() {
		$inDistributionId = 9;
		$outDistributionIds = [1,2,3];
		$products = [
			17 => 1,
			353 => 1,
		];

		$order = DistributionAllocationOrderModel::create($inDistributionId, $outDistributionIds, $products);
		if (Error::isError($order)) {
			$this->fail($order->getError());
		}
	}

	public function testCancel() {
		$number = '32161110659499';
		$order = DistributionAllocationOrderModel::getByNumber($number);
		if (!$order) {
			$this->fail('订单不存在');
		}
		if (!$order->cancel('客户取消')) {
			$this->fail($order->getError());
		}
	}
}