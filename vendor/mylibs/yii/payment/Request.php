<?php
/**
 * 支付请求信息
 *
 * @author suxh
 */

namespace mylibs\yii\payment;

class Request
{
	/**
	 * @var string 订单号
	 */
	public $orderId;

	/**
	 * @var float 金额（元）
	 */
	public $amount;

	/**
	 * @var string 订单时间
	 */
	public $orderTime;
	
	/**
	 * @var string 订单名称
	 */
	public $orderName;
	
	/**
	 * @var string 额外的扩展参数，将原样返回
	 */
	public $extra = null;
	
	public function __construct() {
		$this->orderTime = date('YmdHis');
	}
}
