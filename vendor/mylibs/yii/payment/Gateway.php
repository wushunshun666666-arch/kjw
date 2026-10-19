<?php
/**
 * 在线支付网关基类
 *
 * @author suxh
 */

namespace mylibs\yii\payment;

abstract class Gateway
{
	/**
	 * 工厂方法
	 * @param string $method
	 * @return Gateway
	 */
	public static function factory($method) {
		$maps = [
			'alipay'	=> '\mylibs\yii\payment\AliPayGateway',
			'tenpay'	=> '\mylibs\yii\payment\TenPayGateway',
		];

		$method = strtolower($method);
		if (!isset($maps[$method])) {
			throw new \Exception('unknown payment method: ' . $method);
		}

		$class = $maps[$method];
		return new $class;
	}

	/**
	 * 创建支付请求表单数据
	 * @param RequestUA $request
	 * @return boolean|RequestForm
	 */
	public function buildRequestForm(RequestUA $request) {
		return $this->doBuildRequestForm($request);
	}

	/**
	 * 获取通知信息
	 * 支付网关异步或同步通知时，使用该接口来获取通知信息
	 * @return boolean|Result
	 */
	public function getNotify() {
		return $this->doGetNotify();
	}

	/**
	 * 构造异步通知时，返回给支付网关的失败信息
	 * @param string $message 错误信息
	 */
	public function notifyResponseFailure($message) {
		return $this->doNotifyResponseFailure($message);
	}

	/**
	 * 构造异步通知时，返回给支付网关的成功信息
	 * @param Result $result 支付网关返回的通知信息
	 */
	public function notifyResponseSuccess($result) {
		return $this->doNotifyResponseSuccess($result);
	}

	public function getError() {
		return $this->error;
	}

	/**
	 * 创建支付请求表单数据
	 * @param RequestUA $request
	 * @return boolean|RequestForm
	 */
	abstract protected function doBuildRequestForm(RequestUA $request);

	/**
	 * 获取通知信息
	 * 支付网关异步或同步通知时，使用该接口来获取通知信息
	 * @return boolean|Result
	 */
	abstract protected function doGetNotify();

	/**
	 * 构造异步通知时，返回给支付网关的失败信息
	 * @param string $message 错误信息
	 */
	abstract protected function doNotifyResponseFailure($message);

	/**
	 * 构造异步通知时，返回给支付网关的成功信息
	 * @param Result $result 支付网关返回的通知信息
	 */
	abstract protected function doNotifyResponseSuccess(Result $result);

	protected function setError($error) {
		$this->error = $error;
		return false;
	}

	private $error;
}
