<?php
/**
 * 支付宝网关
 * @author suxh
 */

namespace mylibs\yii\payment;

use \mylibs\payment\AliPay;
use mylibs\payment\AliPayConfig;
use mylibs\yii\config\Configure;

class AliPayGateway extends Gateway
{
	public function __construct() {
		$configure = new Configure();
		$config = $configure->loadCommonConfig('payment/alipay');
		$config = new AliPayConfig($config);

		$logger = new Logger($config->name);

		$this->alipay = new AliPay($config, $logger);
	}

	/**
	 * (non-PHPdoc)
	 * @see Gateway::doBuildRequestForm()
	 */
	protected function doBuildRequestForm(RequestUA $request) {
		$returnUrl 	= $request->frontUrl;
		$notifyUrl 	= $request->backUrl;
		$outTradeNo = $request->orderId;
		$totalFee 	= $request->amount;
		$subject 	= $request->orderName;
		$options 	= array(
			'exter_invoke_ip'=>$request->ip,
			'extra_common_param'=>$request->extra,
		);

		$alipay = $this->alipay;
		$form = $alipay->buildRequestForm($outTradeNo, $totalFee, $subject, $notifyUrl, $returnUrl, $options);
		if (!$form) {
			return $this->setError($alipay->getError());
		}

		return $form;
	}

	/**
	 * (non-PHPdoc)
	 * @see Gateway::doGetNotify()
	 */
	protected function doGetNotify() {
		$alipay = $this->alipay;
		$response = $alipay->getNotify();
		if (!$response) {
			return $this->setError($alipay->getError());
		}

		$result = new Result;
		$result->params			= $response;
		$result->orderId		= trim($response['out_trade_no']);
		$result->orderAmount	= $response['total_fee'];
		$result->extra 			= (isset($response['extra_common_param']))? $response['extra_common_param']:null;

		if (in_array($response['trade_status'], [AliPay::TRADE_FINISHED, AliPay::TRADE_SUCCESS])) {
			$result->success = true;
		} else {
			$result->success = false;
			$result->message = $response['trade_status'];
		}

		return $result;
	}

	protected function doNotifyResponseFailure($message) {
		return 'fail';
	}

	protected function doNotifyResponseSuccess(Result $result) {
		return 'success';
	}

	/**
	 * @var AliPay
	 */
	private $alipay;
}