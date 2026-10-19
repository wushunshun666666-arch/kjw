<?php

namespace tests\unit\models\warehouse;

use Codeception\Specify;
use common\models\Error;
use common\models\storehouse\stock\StockItem;
use common\models\storehouse\stock\StockModel;

/**
 * Login form test
 */
class StockModelTest extends \Codeception\Test\Unit
{

    use Specify;

    public function testInstock() {
    	$storehouseId = 1;
    	$type = StockItem::TYPE_1000;
    	$items = [
    		['locationId'=>1, 'productId'=>6871, 'quantity'=>5],
    		['locationId'=>2, 'productId'=>6876, 'quantity'=>10],
    	];
    	$number = '';
        $res = StockModel::instock($storehouseId, $type, $items, $number);
        $this->assertFalse(Error::isError($res));
    }

    public function testOutstock() {
    	$storehouseId = 1;
    	$type = StockItem::TYPE_1010;
    	$items = [
    		['locationId'=>1, 'productId'=>6871, 'quantity'=>5],
    		['locationId'=>2, 'productId'=>6876, 'quantity'=>10],
    	];
    	$number = '';
    	$res = StockModel::outstock($storehouseId, $type, $items, $number);
    	$this->assertFalse(Error::isError($res));
    }

    public function testMove() {
    	$items = [
    		['sourceLocationId'=>1, 'targetLocationId' => 2128, 'productId'=>6871, 'quantity'=>5],
    		['sourceLocationId'=>2, 'targetLocationId' => 2128, 'productId'=>6876, 'quantity'=>5],
    	];
    	//$res = StockModel::moveStart($items);
    	//$this->assertFalse(Error::isError($res), Error::isError($res)? $res->getError() : '');

    	$res = StockModel::moveCancel($items);
    	$this->assertFalse(Error::isError($res), Error::isError($res)? $res->getError() : '');

    	//$res = StockModel::moveEnd($items);
    	//$this->assertFalse(Error::isError($res), Error::isError($res)? $res->getError() : '');
    }
}
