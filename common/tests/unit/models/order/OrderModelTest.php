<?php

namespace tests\unit\models\distribution;

use Codeception\Specify;
use common\models\Error;
use common\models\LogicModel;
use common\models\order\Order;
use common\models\order\OrderModel;

class OrderModelTest extends \Codeception\Test\Unit
{
	use Specify;

	protected function setUp() {
		parent::setUp();
		$identity = new \common\models\SystemUserIdentity();
		LogicModel::setIdentity($identity);
	}

	public function testCreateByDistribution() {
		$distributionUserId = 9;
		$products = [
			17 => ['quantity' => 1],
			353 => ['quantity' => 1],
		];
		$customerId = 444;
		$paymentMethod = Order::PAYMENT_METHOD_12000;
		$orderDate = '2017-05-21';

		$order = OrderModel::createByDistribution($products, $distributionUserId, $customerId, $paymentMethod, $orderDate);
		if (Error::isError($order)) {
			return $this->fail($order->getError());
		}
	}

	public function testCreate() {
		$distributionId = 9;
		$products = [
			17 => 1,
			353 => 1,
		];
		$customerUserId = 1;

		$order = OrderModel::createByGarage($products, $distributionId, $customerUserId);
		if (Error::isError($order)) {
			return $this->fail($order->getError());
		}
	}

	public function testPay() {
		$order = $this->create();

		if (!$order->pay(Order::PAYMENT_METHOD_10000)) {
			$this->fail($order->getError());
		}
	}

	public function testModifyPaymentMethod() {
		$order = $this->create();

		if (!$order->pay(Order::PAYMENT_METHOD_11000)) {
			$this->fail($order->getError());
		}
		if (!$order->modifyPaymentMethod(Order::PAYMENT_METHOD_10000)) {
			$this->fail($order->getError());
		}
	}

	public function testRefund() {
		$order = $this->create();

		if (!$order->pay(Order::PAYMENT_METHOD_20000)) {
			$this->fail($order->getError());
		}
		if (!$order->cancel()) {
			$this->fail($order->getError());
		}
	}

	public function testAddItems() {
		$order = $this->create();

		$products = [
			12626 => ['quantity' => 2],
		];
		if (!$order->addItems($products)) {
			$this->fail($order->getError());
		}
	}

	public function testModifyItem() {
		$order = $this->create();
		$items = $order->getData()->items;
		$itemId = $items[0]->id;

		$attributes = [
			'quantity' => 2,
			'bargainUnitPrice' => 40,
			'printUnitPrice' => 50,
		];

		if (!$order->modifyItem($itemId, $attributes)) {
			$this->fail($order->getError());
		}
	}

	public function testDeleteItem() {
		$order = $this->create();
		$items = $order->getData()->items;
		$itemId = $items[0]->id;

		if (!$order->deleteItem($itemId)) {
			$this->fail($order->getError());
		}
	}

	private function create() {
		$distributionId = 9;
		$products = [
			17 => 1,
			353 => 1,
		];
		$customerUserId = 5;

		$order = OrderModel::createByGarage($products, $distributionId, $customerUserId);
		if (Error::isError($order)) {
			$this->fail($order->getError());
		}

		return $order;
	}
}