<?php

namespace tests\unit\models\warehouse;

use Codeception\Specify;
use common\models\LogicModel;
use common\models\storehouse\outstock\OutstockOrderModel;

class OutstockOrderModelTest extends \Codeception\Test\Unit
{
	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testCreate() {
	}

	public function testPicking() {
		$number = '21160714000137';
    	$order = OutstockOrderModel::getByNumber($number);
    	$items = $order->getPendingAllocate();
    	if (!$items) {
    		$this->fail($order->getError());
    	}

    	$allocateItems = [];
    	foreach ($items as $item) {
    		$itemId = $item['item']->id;
    		$locations = $item['locations'];
    		foreach ($locations as $location) {
    			$allocateItems[] = [
   					'itemId' => $itemId,
   					'locationId' =>$location['locationId'],
    				'quantity' => $location['quantity'],
    			];
    		}
    	}

    	if (!$order->picking($allocateItems)) {
    		$this->fail($order->getError());
    	}
	}
}