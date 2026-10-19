<?php

namespace tests\unit\models\warehouse;

use Codeception\Specify;
use common\models\storehouse\base\Code;
use common\models\storehouse\rule\AllocateRule;

class AllocateRuleTest extends \Codeception\Test\Unit
{

	use Specify;

	public function testGetDefaultLocations() {
		$rule = new AllocateRule();
		$orderType = Code::OUTSTOCK_ORDER_TYPE_1010;
		$storehouseId = 1;
		$productId = 2242;
		$quantity = 10;
		$locations = $rule->getDefaultLocations($orderType, $storehouseId, $productId, $quantity);
		$this->fail(var_export($locations, true));
	}
}