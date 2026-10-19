<?php

namespace tests\unit\models\distribution;

use Codeception\Specify;
use common\models\LogicModel;
use common\models\distribution\allocation\DistributionAllocationPriceRule;

class DistributionAllocationPriceRuleTest extends \Codeception\Test\Unit
{
	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testPrice() {
		$rule = new DistributionAllocationPriceRule();

		$categoryId = 10010001;
		$brandId = 8;
		$joinPrice = 100;
		$price = $rule->getPrice($categoryId, $brandId, $joinPrice);

		if ($price != 110) {
			$this->fail("expect: 110, real:$price");
		}

		$categoryId = 10010001;
		$brandId = 9;
		$joinPrice = 100;
		$price = $rule->getPrice($categoryId, $brandId, $joinPrice);

		if ($price != 130) {
			$this->fail("expect: 110, real:$price");
		}

		$categoryId = 10010001;
		$brandId = 9;
		$joinPrice = 0.01;
		$price = $rule->getPrice($categoryId, $brandId, $joinPrice);

		if ($price != 0.01) {
			$this->fail("expect: 0.01, real:$price");
		}

		$categoryId = 10010001;
		$brandId = 9;
		$joinPrice = 0.02;
		$price = $rule->getPrice($categoryId, $brandId, $joinPrice);

		if ($price != 0.03) {
			$this->fail("expect: 0.03, real:$price");
		}
	}
}