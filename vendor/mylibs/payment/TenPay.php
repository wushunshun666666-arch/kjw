<?php
/**
 * TenPay.php
 * @author James
 */

namespace mylibs\payment;

class TenPay
{
	const TYPE_NATIVE = 'NATIVE';
	const TYPE_JSAPI = 'JSAPI';

	/**
	 * 构造函数
	 * @param AliPayConfig $config
	 * @param Logger $logger
	 */
	public function __construct(TenPayConfig $config, $logger = null) {
		$this->config = $config;
		if (!$logger) {
			$logger = new Logger();
		}

		$this->logger = $logger;
	}

	/**
	 * 扫码支付地址
	 * @param string $orderId 系统订单号
	 */
	public function nativePayUrl($orderId) {
		$params = [
			'appid'			=> $this->config->appId,
			'mch_id'		=> $this->config->mchId,
			'product_id'	=> $orderId,
			'time_stamp'	=> time(),
			'nonce_str'		=> $this->nonceStr(),
		];

		$params['sign'] = $this->sign($params);
		return $this->config->nativePayUrl.'?'.http_build_query($params);
	}

	/**
	 * 生成短链接
	 * @param string $longUrl
	 */
	public function shortUrl($longUrl) {
		$params = [
			'appid'			=> $this->config->appId,
			'mch_id'		=> $this->config->mchId,
			'long_url'		=> urlencode($longUrl),
			'nonce_str'		=> $this->nonceStr(),
		];
		$params['sign'] = $this->sign($params);
		return $this->send('tools/shorturl', $params);
	}

	/**
	 * 订单查询
	 * @param string $transactionId
	 */
	public function query($transactionId) {
		$params = [
			'appid'				=> $this->config->appId,
			'mch_id'			=> $this->config->mchId,
			'transaction_id'	=> $transactionId,
			'nonce_str'			=> $this->nonceStr(),
		];
		$params['sign'] = $this->sign($params);
		return $this->send('pay/orderquery', $params);
	}

	/**
	 * 统一下单
	 * @param string  $orderId
	 * @param integer $amount  不含小数，单位分
	 * @param string  $summary
	 * @param string  $notifyUrl
	 * @return boolean|\mylibs\payment\false|mixed
	 */
	public function unifiedorder($orderId, $amount, $summary, $notifyUrl, $tradeType=self::TYPE_NATIVE, $openid=null) {
		if ($tradeType == self::TYPE_JSAPI && empty($openid)) {
			return $this->setError('公众号支付时必须传openid');
		}

		$params = [
			'appid'				=> $this->config->appId,
			'mch_id'			=> $this->config->mchId,
			'nonce_str'			=> $this->nonceStr(),
			'body'				=> $summary,
			'out_trade_no'		=> $orderId,
			'total_fee'			=> $amount,
			'spbill_create_ip'	=> '',
			'time_start'		=> time(),
			'notify_url'		=> $notifyUrl,
			'trade_type'		=> $tradeType,
		];

		if ($tradeType == self::TYPE_JSAPI) {
			$params['openid'] = $openid;
		}

		$params['sign'] = $this->sign($params);
		return $this->send('pay/unifiedorder', $params);
	}

	/**
	 * 扫码支付预支付流程回调
	 * @param string $xml
	 */
	public function getNotify() {
		$xml = file_get_contents("php://input");
		if (empty($xml)) {
			return $this->setError('通知内容为空。');
		}

		$this->logger->log($xml, 'NOTIFY REQUEST');

		$result = $this->parseXml($xml);
		if (!$result) {
			return false;
		}

		if (!$this->verifySign($result)) {
			return false;
		}

		return $result;
	}

	/**
	 * 检查回调通知签名
	 * @param array $params
	 */
	public function verifySign($result) {
		if (!isset($result['sign'])) {
			return $this->setError('签名校验失败');
		}

		$sign = $result['sign'];
		unset($result['sign']);
		if ($sign != $this->sign($result)) {
			return $this->setError('签名校验失败');
		}

		return true;
	}

	/**
	 * 扫码支付回调-支付成功
	 * @param string $prepayId
	 */
	public function nativePayNotifySuccess($prepayId) {
		return $this->nativePayNotifyResponse('SUCCESS', 'OK', [
			'prepay_id' => $prepayId
		]);
	}

	/**
	 * 扫码支付回调-支付失败
	 * @param string $prepayId
	 * @param string $errMsg
	 */
	public function nativePayNotifyFail($errMsg="") {
		return $this->nativePayNotifyResponse('FAIL', $errMsg);
	}

	/**
	 * 公众号支付
	 * @param unknown $prepay_id
	 */
	public function publicPay($prepay_id) {
		$params = [
			'appId'			=> $this->config->appId,
			'timeStamp'		=> ''.time(),
			'nonceStr'		=> $this->nonceStr(),
			'package'		=> 'prepay_id='.$prepay_id,
			'signType'		=> $this->config->signType,
		];
		$params['paySign'] = $this->sign($params);

		return $params;
	}

	private function nativePayNotifyResponse($resultCode, $errMsg, $params=[]) {
		$params = array_merge($params, [
			'appid'				=> $this->config->appId,
			'mch_id'			=> $this->config->mchId,
			'nonce_str'			=> $this->nonceStr(),
			'result_code'		=> $resultCode,
			'err_code_des'		=> $errMsg
		]);
		return $this->notifySuccess($params, true);
	}

	/**
	 * 通信成功回调，非业务成功。业务成功已否由$params['result_code']标识
	 * @param array $params
	 * @param string $sign
	 */
	public function notifySuccess($params=[], $sign=false) {
		$params = array_merge($params, [
			'return_code'	=> 'SUCCESS',
			'return_msg'	=> 'OK',
		]);
		if ($sign) {
			$params['sign'] = $this->sign($params);
		}

		return $this->notifyResponse($params);
	}

	/**
	 * 通信失败回调，非业务失败
	 * @param unknown $message
	 */
	public function notifyFail($message) {
		return $this->notifyResponse([
			'return_code'	=> 'FAIL',
			'return_msg'	=> $message,
		]);
	}

	/**
	 * 通知回复
	 * @param unknown $params
	 */
	private function notifyResponse($params) {
		$response = $this->arrayToXml($params);
		$this->logger->log($response, 'NOTIFY RESPONSE');
		return $response;
	}

	/**
	 * 发送请求
	 * @param string $action
	 * @param array  $params
	 */
	private function send($action, $params) {
		$url = $this->config->apiUrl.$action;
		$response = $this->httpPost($url, $this->arrayToXml($params));
		if (!$response) {
			return false;
		}

		$result = $this->parseXml($response);
		if (!$result) {
			return false;
		}

		if ($result['return_code'] == 'FAIL') {
			return $this->setError($result['return_msg']);
		}

		if (isset($result['result_code']) && $result['result_code'] == 'FAIL') {
			return $this->setError($result['err_code_des']);
		}

		return $result;
	}

	/**
	 * 生成签名
	 * @param array $params 参数对应的键值对
	 * @return string
	 */
	private function sign($params) {
		$sign = '';
		ksort($params);
		foreach ($params as $key => $value) {
			if (empty($value)) continue;
			$sign .= "{$key}={$value}&";
		}
		$sign .= 'key='.$this->config->key;
		$sign = strtoupper(md5($sign));
		return $sign;
	}

	/**
	 * 生成随机数算法
	 * @return string
	 */
	private function nonceStr() {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$len = strlen($chars)-1;

		$str = '';
		for ($i=0; $i < 32; $i++) {
			$index = rand(0, $len);
			$str .= $chars[$index];
		}

		return $str;
	}

	/**
	 * 数组=>xml
	 * @param unknown $params
	 */
	private function arrayToXml($params) {
		$xml = '<xml>';
		foreach ($params as $key => $value) {
			$xml .= "<{$key}><![CDATA[{$value}]]></{$key}>";
		}
		$xml .= '</xml>';

		return $xml;
	}

	/**
	 * xml=>数组
	 * @param string $xml
	 */
	private function xmlToArray($xml) {
		$xml = @simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
		return json_decode(json_encode((Array)$xml), true);
	}

	/**
	 * 解析放回数据
	 * @param object $response
	 */
	private function parseXml($xml) {
		$result = $this->xmlToArray($xml);
		if (!$result) {
			return $this->setError('数据解析失败。');
		}

		return $result;
	}

	/**
	 * 请求
	 * @param string $url
	 * @param string $xml
	 */
	private function httpPost($url, $xml) {
		$this->logger->logRequest($xml, $url);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	//设置是否返回信息
		curl_setopt($ch, CURLOPT_POST, 1);				//设置为POST方式
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);		//POST数据
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		$response = curl_exec($ch);

		if($response === false) {
			$rs = curl_error($ch);
			return $this->setError('获取数据失败:'.$rs);
		}

		$status = curl_getinfo($ch);

		$this->logger->logResponse($response);

		if(intval($status['http_code'])!=200){
			return $this->setError($status['http_code']);
		}

		return $response;
	}

	/**
	 * 设置错误信息
	 * @param string $error
	 * @param boolean $log
	 * @return false
	 */
	protected function setError($error, $log = true) {
		$this->error = $error;
		$this->logger->log($error, 'ERROR');

		return false;
	}

	/**
	 * @return string 返回错误信息
	 */
	public function getError() {
		return $this->error;
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
