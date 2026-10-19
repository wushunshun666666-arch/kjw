<?php

namespace tests\unit\models\accounting;

use Codeception\Specify;
use common\models\Error;
use common\models\LogicModel;
use common\models\accounting\rp\RpAccountItem;
use common\models\accounting\rp\RpAccountItemModel;
use common\models\accounting\rp\RpAccountModel;
use common\models\accounting\rp\RpAccount;
use common\models\product\product\Supplier;

class RpAccountModelTest extends \Codeception\Test\Unit
{
	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testReceivable() {
		$supplier = Supplier::findById(1);

		$account = RpAccountModel::getBySupplier($supplier->id);
		if (!$account) {
			$customer = [
				'type' => RpAccount::CUSTOMER_TYPE_5000,
				'id' => $supplier->id,
				'name' => $supplier->name,
			];
			$account = RpAccountModel::create($customer);
			if (Error::isError($account)) {
				$this->fail($account->getError());
			}
		}

		$amount 	= 53083;
		$typeId 	= 2001000;
		$billTypeId = 2001000;
		$billNumber = '50161118245965';
		$note = '马顿后刹车片等，数量：1625';
		if (!$account->receivable($amount, $typeId, $billTypeId, $billNumber, $note)) {
			$this->fail($account->getError());
		}
	}

	public function testPayable() {
		$supplier = Supplier::findById(1);

		$account = RpAccountModel::getBySupplier($supplier->id);
		if (!$account) {
			$customer = [
				'type' => RpAccount::CUSTOMER_TYPE_5000,
				'id' => $supplier->id,
				'name' => $supplier->name,
			];
			$account = RpAccountModel::create($customer);
			if (Error::isError($account)) {
				$this->fail($account->getError());
			}
		}

		$amount 	= 53083;
		$typeId 	= 2001000;
		$billTypeId = 2001000;
		$billNumber = '50161118245965';
		$note = '马顿后刹车片等，数量：1625';
		if (!$account->payable($amount, $typeId, $billTypeId, $billNumber, $note)) {
			$this->fail($account->getError());
		}
	}

	public function testClosing() {
		$supplier = Supplier::findById(1);

		$account = RpAccountModel::getBySupplier($supplier->id);
		if (!$account) {
			$customer = [
				'type' => RpAccount::CUSTOMER_TYPE_5000,
				'id' => $supplier->id,
				'name' => $supplier->name,
			];
			$account = RpAccountModel::create($customer);
			if (Error::isError($account)) {
				$this->fail($account->getError());
			}
		}

		$amount 	= 53083;
		$typeId 	= 2001000;
		$billTypeId = 2001000;
		$billNumber = '50161118245965';
		$note = '马顿后刹车片等，数量：1625';
		if (!$account->payable($amount, $typeId, $billTypeId, $billNumber, $note)) {
			$this->fail($account->getError());
		}

		$item = RpAccountItem::find()->limit(1)->orderBy(['id' => SORT_DESC])->one();
		$item = new RpAccountItemModel($item);
		$methodId = 1001000;
		$amount = 20000;
		if (!$item->closing($amount, $methodId)) {
			$this->fail($item->getError());
		}

		$item->getData()->refresh();
		if (!$item->closing(33000, $methodId)) {
			$this->fail($item->getError());
		}
		if (!$item->complete()) {
			$this->fail($item->getError());
		}
	}
}