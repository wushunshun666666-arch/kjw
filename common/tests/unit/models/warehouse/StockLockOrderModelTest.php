<?php

namespace tests\unit\models\warehouse;

use Codeception\Specify;
use common\models\LogicModel;
use common\models\storehouse\stock\StockLockOrderModel;

/**
 * Login form test
 */
class StockLockOrderModelTest extends \Codeception\Test\Unit
{

	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testSplit() {
		$id = 22804;
		$order = StockLockOrderModel::getById($id);
		if (!$order) {
			return $this->assertFalse('锁定单不存在');
		}

		$attributes = [];
		$attributes['relatedNumber'] = '30170322110754994752';
		$splitItems = [11808];
		if (!$order->split($attributes, $splitItems)) {
			return $this->assertFalse($order->getError());
		}
	}
}
