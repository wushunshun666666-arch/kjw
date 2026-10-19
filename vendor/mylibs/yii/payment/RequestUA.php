<?php
/**
 * 支付请求信息，通过浏览器支付
 *
 * @author suxh
 */

namespace mylibs\yii\payment;

class RequestUA extends Request
{
	/**
	 * @var string 前端回调地址
	 */
	public $frontUrl;

	/**
	 * @var string 后端回调地址
	 */
	public $backUrl;

	/**
	 * @var string 用户ID
	 */
	public $ip;

	/**
	 * 构造函数
	 */
	public function __construct() {
		parent::__construct();
		$this->ip = \Yii::$app->request->getUserIP();
	}
}
