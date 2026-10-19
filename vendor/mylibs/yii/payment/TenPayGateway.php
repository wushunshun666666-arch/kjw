<?php
/**
 * TenPayGateway.php
 * @author James
 */

namespace mylibs\yii\payment;

use mylibs\payment\TenPayConfig;
use mylibs\payment\TenPay;
use mylibs\yii\config\Configure;

class TenPayGateway extends Gateway
{
	public function __construct() {
		$configure = new Configure();
		$config = $configure->loadCommonConfig('payment/tenpay');
		$config = new TenPayConfig($config);

		$logger = new Logger($config->name);
		$this->tenpay = new TenPay($config, $logger);
	}

	/**
	 * 扫码支付短链接
	 * @param string $orderId
	 */
	public function getPayUrl($orderId) {
		$tenpay = $this->tenpay;

		$url = $tenpay->nativePayUrl($orderId);

		$result = $tenpay->shortUrl($url);
		if (!$result) {
			return $this->setError($tenpay->getError());
		}

		return $result['short_url'];
	}

	/**
	 * 公众号支付
	 * @param unknown $amount
	 */
	public function publicPay($openid, $orderId, $amount, $summary, $notifyUrl) {
		$order = $this->order($orderId, $amount, $summary, $notifyUrl, TenPay::TYPE_JSAPI, $openid);
		if (!$order) {
			return false;
		}

		$data = $this->tenpay->publicPay($order['prepay_id']);
		if (!$data) {
			return $this->setError($this->tenpay->getError());
		}

		return $data;
	}

	/**
	 * 统一下单
	 * @param string $orderId
	 * @param string $amount 单位元，精确到分
	 * @param string $summary
	 * @param string $notifyUrl 回调地址
	 */
	public function order($orderId, $amount, $summary, $notifyUrl, $tradeType=TenPay::TYPE_NATIVE, $openid=null) {
		$tenpay = $this->tenpay;

		if ($amount <= 0) {
			return $this->setError('付款金额必须大于0。');
		}

		$amount = round($amount, 2)*100;  // 元=>分

		$result = $tenpay->unifiedorder($orderId, $amount, $summary, $notifyUrl, $tradeType, $openid);
		if (!$result) {
			return $this->setError($tenpay->getError());
		}

		return $result;
	}

	/**
	 * 扫码支付预支付流程回调通知
	 * @param string $response
	 */
	public function nativePayNotify() {
		$tenpay = $this->tenpay;
		$result = $tenpay->getNotify();
		if (!$result) {
			return $this->setError($tenpay->getError());
		}

		if (!isset($result['product_id'])) {
			return $this->setError('参数校验错误');
		}

		return $result;
	}

	public function nativePayNotifySuccess($prepayId) {
		return $this->tenpay->nativePayNotifySuccess($prepayId);
	}

	public function nativePayNotifyFail($message) {
		return $this->tenpay->nativePayNotifyFail($message);
	}

	/**
	 * (non-PHPdoc)
	 * @see Gateway::doBuildRequestForm()
	 */
	protected function doBuildRequestForm(RequestUA $request) {
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Gateway::doGetNotify()
	 */
	protected function doGetNotify() {
		$tenpay = $this->tenpay;
		$notify = $tenpay->getNotify();
		if (!$notify) {
			return $this->setError($tenpay->getError());
		}

		// 通信失败
		if ($notify['return_code'] == 'FAIL') {
			return $this->setError($notify['return_msg']);
		}

		$result = new Result();
		$result->params			= $notify;
		$result->orderId		= $notify['out_trade_no'];
		$result->orderAmount	= $notify['total_fee'];
		if ($notify['result_code'] == 'FAIL') {
			// 业务失败
			$result->success = false;
			$result->message = $notify['err_code'].'：'.$notify['err_code_des'];
		} else {
			$result->success = true;
		}

		return $result;
	}

	protected function doNotifyResponseFailure($message) {
		return $this->tenpay->notifyFail($message);
	}

	protected function doNotifyResponseSuccess(Result $result) {
		return $this->tenpay->notifySuccess();
	}

	private $tenpay;
}