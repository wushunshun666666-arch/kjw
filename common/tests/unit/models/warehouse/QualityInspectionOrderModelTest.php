<?php

namespace tests\unit\models\warehouse;

use Codeception\Specify;
use common\models\Error;
use common\models\LogicModel;
use common\models\storehouse\instock\QualityInspectionOrderModel;

class QualityInspectionOrderModelTest extends \Codeception\Test\Unit
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
		$attributes['instockId'] = 1;
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

		$res = QualityInspectionOrderModel::create($attributes, $items);
		$this->assertFalse(Error::isError($res), $res->getError());
	}
}
