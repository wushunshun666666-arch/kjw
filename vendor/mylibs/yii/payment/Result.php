<?php
/**
 * 支付结果
 *
 * @author suxh
 */

namespace mylibs\yii\payment;

class Result
{
	/**
	 * @var string 订单号
	 */
	public $orderId;

	/**
	 * @var string 订单金额（元）
	 * 注：可能存在支付商优惠的情况，因此这里返回的是订单金额，而非用户实际支付额
	 */
	public $orderAmount;

	/**
	 * @var string 返回支付结果的IP地址
	 */
	public $ip;

	/**
	 * @var boolean 是否支付成功
	 */
	public $success;

	/**
	 * @var string 失败信息
	 */
	public $message;

	/**
	 * @var string 额外的扩展参数
	 */
	public $extra = null;

	/**
	 * @var string 额外的扩展参数(通过缓存实现)
	 */
	public $myExtra = null;
	
	/**
	 * @var array 支付网关返回的原始参数
	 */
	public $params = [];

	/**
	 * 构造函数
	 */
	public function __construct() {
		$this->ip = \Yii::$app->request->getUserIP();
	}
}
