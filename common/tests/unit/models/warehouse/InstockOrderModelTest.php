<?php

namespace tests\unit\models\warehouse;

use Codeception\Specify;
use common\models\Error;
use common\models\LogicModel;
use common\models\storehouse\instock\InstockOrder;
use common\models\storehouse\instock\InstockOrderModel;

class InstockOrderModelTest extends \Codeception\Test\Unit
{
    use Specify;

    protected function setUp() {
    	parent::setUp();
    	$identity = new \common\models\SystemUserIdentity();
    	LogicModel::setIdentity($identity);
    }

    public function testCreate() {
    	$attributes = [];
    	$attributes['storehouseId'] = 1;
    	$attributes['type'] = InstockOrder::TYPE_1000;
    	$attributes['relatedNumber'] = '50160611120071';
    	$attributes['arrivalDate'] = '2016-06-20';
    	$attributes['userId'] = 99;
    	$attributes['note'] = '';

    	$items = [
    		[
    			'productId' 	=> 1,
    			'quantity' 		=> 10,
    			'productNumber' => 'M00020010000',
    			'supplierNumber'=> 'RB1227',
    			'productName' 	=> '索易后刹车片',
    			'productCategory' => '后刹车片',
    			'productBrand' 	=> '索易',
    			'productUnit' 	=> '副',
    		]
    	];

    	$consignor = [
    		'name'			=> 'test',
    		'contactName'	=> 'test',
    		'contactTel'	=> '12585858585',
    		'contactMobi'	=> '12585858585',
    		'contactEmail'	=> 'test@test.com',
    		'provinceId'	=> '8140000',
    		'cityId'		=> '8140100',
    		'districtId'	=> '8140107',
    		'address'		=> 'test',
    	];

        $res = InstockOrderModel::create($attributes, $items, $consignor);
        $this->assertFalse(Error::isError($res), $res->getError());
    }

    public function testReceive() {
    	$number = '20160712000122';
    	$order = InstockOrderModel::getByNumber($number);

    	$items = [
    		[
    			'itemId' => 15566,
    			'locationId' => 2572,
    			'quantity' => 5,
    		]
    	];

    	$date = date('Y-m-d');
    	$userId = 1;
    	$attributes = [];
    	$attributes['date'] = $date;
    	$attributes['userId'] = $userId;
    	if (!$order->receive($attributes, $items)) {
    		$this->fail($order->getError());
    	}
    }

    public function testPutaway() {
    	$number = '20160712000122';
    	$order = InstockOrderModel::getByNumber($number);
    	$items = [
    		[
    			'itemId' => 45,
    			'locationId' => 223,
    			'quantity' => 10,
    		]
    	];
    	if (!$order->putaway($items)) {
    		$this->fail($order->getError());
    	}
    }

    public function testCancelReceive() {
    	$number = '20160712000122';
    	$order = InstockOrderModel::getByNumber($number);

    	$itemId = 44;
    	if (!$order->cancelReceive($itemId)) {
    		$this->fail($order->getError());
    	}
    }
}
