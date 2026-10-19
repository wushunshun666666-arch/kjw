<?php

namespace tests\unit\models\distribution;

use Codeception\Specify;
use common\models\LogicModel;
use common\models\Error;
use common\models\garage\GarageModel;

class GarageModelTest extends \Codeception\Test\Unit
{
	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testActivate() {
		$id = 1;
		$garage = GarageModel::getById($id);
		if (!$garage) {
			$this->fail('修理厂不存在');
		}
		if (!$garage->activate('12585855568', '654123')) {
			$this->fail($garage->getError());
		}
	}

	private function create() {
		$attributes = [];
		$attributes['name'] 		= '汽修厂4';
		$attributes['district_id'] 	= 8110105;
		$attributes['address'] 		= 'test';
		$attributes['contact_name'] = '张三';
		$attributes['contact_tel'] 	= '25858578';
		$garage = GarageModel::create($attributes);
		if (Error::isError($garage)) {
			$this->fail($garage->getError());
		}
		return $garage;
	}
}