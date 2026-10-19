<?php
/**
 * AliPay.php
 * @author suxh
 */

namespace mylibs\payment;

class AliPay
{
	const TRADE_FINISHED = 'TRADE_FINISHED';
	const TRADE_SUCCESS = 'TRADE_SUCCESS';

	/**
	 * 构造函数
	 * @param AliPayConfig $config
	 * @param Logger $logger
	 */
	public function __construct(AliPayConfig $config, $logger = null) {
		$this->config = $config;
		if (!$logger) {
			$logger = new Logger();
		}

		$this->logger = $logger;
	}

	/**
	 * 构造请求表单
	 * @param string $outTradeNo 支付宝合作商户网站唯一订单号（确保在商户系统中唯一）
	 * @param string $totalFee 该笔订单的资金总额，单位为RMB-Yuan。取值范围为[0.01，100000000.00]，精确到小数点后两位。
	 * @param string $subject 商品的标题/交易标题/订单标题/订单关键字等
	 * @param string $notifyUrl 支付宝服务器主动通知商户网站里指定的页面http路径。不能带?id=123这类自定义参数。
	 * @param string $returnUrl 支付宝处理完请求后，当前页面自动跳转到商户网站里指定页面的http路径。不能带?id=123这类自定义参数。
	 * @param array $options 其它参数
	 * @return RequestForm 返回支付表单信息
	 */
	public function buildRequestForm($outTradeNo, $totalFee, $subject, $notifyUrl = '', $returnUrl = '', $options = array()) {
		$config = $this->config;

		$defaultOptions = array(
			'body'			=>'',
			'paymethod'		=>'',
			'defaultbank'	=>'',
			'show_url'		=>'',
			'exter_invoke_ip'=>$this->getIp(),
			'extra_common_param'=>'',
		);

		$options = array_merge($defaultOptions, $options);

		$form = new RequestForm();
		$form->action = $config->gatewayUrl."_input_charset=".$config->inputCharset;
		$form->method = 'POST';

		// 接口名称
		$form->setField('service', 'create_direct_pay_by_user');
		// 签约的支付宝账号对应的支付宝唯一用户号。以2088开头的16位纯数字组成。
		$form->setField('partner', $config->partner);
		// 1表示商品购买
		$form->setField('payment_type', 1);
		// 支付宝服务器主动通知商户网站里指定的页面http路径。
		$form->setField('notify_url', $notifyUrl);
		// 支付宝处理完请求后，当前页面自动跳转到商户网站里指定页面的http路径。
		$form->setField('return_url', $returnUrl);
		// 卖家支付宝账号对应的支付宝唯一用户号。以2088开头的纯16位数字。
		$form->setField('seller_email', $config->sellerEmail);
		// 支付宝合作商户网站唯一订单号（确保在商户系统中唯一）。
		$form->setField('out_trade_no', $outTradeNo);
		// 商品的标题/交易标题/订单标题/订单关键字等。该参数最长为128个汉字。
		$form->setField('subject', $subject);
		// 该笔订单的资金总额，单位为RMB-Yuan。取值范围为[0.01，100000000.00]，精确到小数点后两位。
		$form->setField('total_fee', $totalFee);
		// 对一笔交易的具体描述信息。如果是多种商品，请将商品描述字符串累加传给body。
		$form->setField('body', $options['body']);
		// 若要使用纯网关，取值必须是bankPay（网银支付）。如果不设置，默认为directPay（余额支付）。
		$form->setField('paymethod', $options['paymethod']);
		// 默认网银，银行简码
		$form->setField('defaultbank', $options['defaultbank']);
		// 收银台页面上，商品展示的超链接。
		$form->setField('show_url', $options['show_url']);
		// 通过时间戳查询接口获取的加密支付宝系统时间戳。如果已申请开通防钓鱼时间戳验证，则此字段必填。
		//$form->setField('anti_phishing_key', );
		// 用户在创建交易时，该用户当前所使用机器的IP。如果商户申请后台开通防钓鱼选项，此字段必填，校验用。
		$form->setField('exter_invoke_ip', $options['exter_invoke_ip']);
		// 商户网站使用的编码格式，如utf-8、gbk、gb2312等。
		$form->setField('_input_charset', $config->inputCharset);
		// 公用回传参数，如果用户请求时传递了该参数，则返回给商户时会回传该参数。
		$form->setField('extra_common_param', $options['extra_common_param']);

		// 签名结果与签名方式加入请求提交参数组中
		$mysign = $this->sign($form->fields, $config->key, $config->signType);
		$form->setField('sign', $mysign);
		$form->setField('sign_type', $config->signType);

		$this->logger->logRequest($form->fields, $form->action);
		return $form;
	}

	/**
	 * 取得通知信息
	 * @param array $params
	 * @return array
	 */
	public function getNotify($params = null) {
		if ($params === null) {
			$params = array_merge($_GET, $_POST);
		}

		$this->logger->logResponse($params);

		if (empty($params)) {
			return $this->setError('未接收到返回参数');
		}

		// 校验签名是否正确
		$config = $this->config;
		if (!$this->verifySign($params, $config->key, $config->signType)) {
			return false;
		}

		// 验证是否是支付宝发来的消息，如果失败，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
		$responseTxt = 'true';
		if (isset($params['notify_id'])) {
			$responseTxt = $this->getResponse($params['notify_id']);
		}

		$this->logger->logResponse($responseTxt);

		if (!preg_match('/true$/i', $responseTxt)) {
			return $this->setError('验证请求失败');
		}

		if (!in_array($params['trade_status'], [self::TRADE_FINISHED, self::TRADE_SUCCESS])) {
			$this->logger->log($params['trade_status'], 'ERROR');
		}

		return $params;
	}

	/**
	 * 设置错误信息
	 * @param string $error
	 * @param boolean $log
	 * @return false
	 */
	protected function setError($error, $log = true) {
		$this->error = $error;
		if ($log) {
			$this->logger->log($error, 'ERROR');
		}

		return false;
	}

	/**
	 * @return string 返回错误信息
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * 签名
	 * @param array $params 待签名参数
	 * @param string $key 私钥
	 * @param string $type 签名方式
	 * @return string 返回签名
	 */
	protected function sign($params, $key, $type) {
		// 除去待签名参数数组中的空值和签名参数
		$params = $this->paramsFilter($params);

		// 对待签名参数数组排序
		$params = $this->paramsSort($params);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$paramstr = $this->createLinkstring($params);
		// 生成签名结果
		switch (strtoupper(trim($type))) {
			case 'MD5':
				$mysign = $this->md5Sign($paramstr, $key);
				break;
			default :
				$mysign = '';
		}

		return $mysign;
	}

	/**
	 * 校验签名
	 * @param array $params 参数
	 * @param string $key 私钥
	 * @param string $type 签名方式
	 * @return boolean 返回校验是否成功
	 */
	protected function verifySign($params, $key, $type) {
		if (!isset($params['sign'])) {
			return $this->setError('无签名信息');
		}
		$sign = $params['sign'];
		// 除去待签名参数数组中的空值和签名参数
		$params = $this->paramsFilter($params);

		// 对待签名参数数组排序
		$params = $this->paramsSort($params);

		//把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
		$paramstr = $this->createLinkstring($params);

		// 生成签名结果
		switch (strtoupper(trim($type))) {
			case 'MD5':
				$isSgin = $this->md5Verify($paramstr, $key, $sign);
				break;
			default :
				$isSgin = false;
		}

		if (!$isSgin) {
			return $this->setError('签名校验失败');
		}

		return true;
	}

	private function paramsFilter($params) {
		$paramsFilter = array();
		foreach ($params as $key=>$value) {
			if($key == 'sign' || $key == 'sign_type' || $value == '') {
				continue;
			}
			else {
				$paramsFilter[$key] = $params[$key];
			}
		}

		return $paramsFilter;
	}

	private function paramsSort($params) {
		ksort($params);
		reset($params);
		return $params;
	}

	private function createLinkstring($params) {
		$arg  = "";
		while (list ($key, $val) = each ($params)) {
			$arg.=$key."=".$val."&";
		}
		//去掉最后一个&字符
		$arg = substr($arg,0,count($arg)-2);

		//如果存在转义字符，那么去掉转义
		if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

		return $arg;
	}

	private function md5Sign($paramstr, $key) {
		return md5($paramstr . $key);
	}

	private function md5Verify($paramstr, $key, $sign) {
		$mysgin = md5($paramstr . $key);

		return ($mysgin == $sign);
	}

	/**
	 * @return string 返回用户IP
	 */
	private function getIp() {
		return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';
	}

	private function getResponse($notifyId) {
		$config = $this->config;
		$cacert = $config->cacert;
		$veryfyUrl = $config->getVerifyUrl($notifyId);

		$responseTxt = $this->curlGet($veryfyUrl, $cacert);

		return $responseTxt;
	}

	/**
	 * 远程获取数据，GET模式
	 * @param $url 请求地址
	 * @param $caCertPath CA证书路径
	 * @return string 返回远程输出的数据
	 */
	private function curlGet($url, $caCertPath) {
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
		curl_setopt($curl, CURLOPT_CAINFO,$caCertPath);//证书地址

		$responseText = curl_exec($curl);

		return $responseText;
	}

	/**
	 * @var AliPayConfig 配置
	 */
	protected $config;

	/**
	 * @var Logger 日志记录
	 */
	protected $logger;

	/**
	 * @var string 错误信息
	 */
	private $error;
}
