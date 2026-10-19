<?php
/**
 * Sms253.php
 *
 * @author James
 */

namespace mylibs\sms\sms253;

use mylibs\sms\base\ISms;

class Sms253 implements ISms
{
	public function __construct($config, $logger=null) {
		$this->config = $config;
		$this->logger = $logger;
	}

	public function getError() {
		return $this->error;
	}

	public function send($mobile, $message, $options=null) {
		$mobile = is_array($mobile) ? implode(',', $mobile) : $mobile;

		$attributes = [
			'un'	=> $this->config['account'],
			'pw'	=> $this->config['password'],
			'msg'	=> $message,
			'phone'	=> $mobile,
			'rd'	=> 0
		];

		return $this->sendPostCommand($this->config['url'] , $attributes);
	}

	private function sendPostCommand($url, $data) {
		$result = $this->httpPost($url, $data);
		if (!$result) {
			return false;
		}

		return $this->execResult($result);
	}

	/**
	 * 处理返回值
	 * @param unknown $result
	 */
	private function execResult($result){
		$result = preg_split("/[,\r\n]/", $result);
		$code = $result[1];
		if ($code != 0) {
			return $this->setError($this->errorMessage($code));
		}

		return [
			'id'	=> $result[2],
		];
	}

	/**
	 * 通过CURL发送HTTP请求
	 * @param string $url  //请求URL
	 * @param array $postFields //请求参数
	 * @return mixed
	 */
	private function httpPost($url, $postFields){
		$postFields = http_build_query($postFields);
		$curl = curl_init ();
		curl_setopt ( $curl, CURLOPT_POST, 1 );
		curl_setopt ( $curl, CURLOPT_HEADER, 0 );
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $curl, CURLOPT_URL, $url );
		curl_setopt ( $curl, CURLOPT_POSTFIELDS, $postFields );
		$response = curl_exec ( $curl );
		if ($response === false) {
			$error = curl_error($curl);
			$errno = curl_errno($curl);
			$this->log("REQUEST:\r\n{$url}?{$postStr}\r\n");
			return $this->setError($error."($errno)");
		}
		$status = curl_getinfo($curl);

		$this->log("REQUEST:\r\n{$url}?{$postFields}\r\nRESPONSE:\r\n{$response}\r\n");

		if(intval($status['http_code'])!=200){
			return $this->setError($status['http_code']);
		}

		return $response;
	}

	private function log($message, $level='DEBUG') {
		if ($this->logger) {
			$this->logger->log($message, $level);
		}
	}

	protected function setError($error) {
		$this->error = $error;
		$this->log($error, 'ERROR');
		return false;
	}

	private function errorMessage($code) {
		$map = [
			0	=> '提交成功',
			101	=> '无此用户',
			102	=> '密码错误',
			103	=> '提交过快（提交速度超过流速限制）',
			104	=> '系统忙（因平台侧原因，暂时无法处理提交的短信）',
			105	=> '敏感短信（短信内容包含敏感词）',
			106	=> '消息长度错（>536或<=0）',
			107	=> '包含错误的手机号码',
			108	=> '手机号码个数错（群发>50000或<=0）',
			109	=> '无发送额度（该用户可用短信数已使用完）',
			110	=> '不在发送时间内',
			113	=> 'extno格式错（非数字或者长度不对）',
			116	=> '签名不合法或未带签名（用户必须带签名的前提下）',
			117	=> 'IP地址认证错,请求调用的IP地址不是系统登记的IP地址',
			118	=> '用户没有相应的发送权限（账号被禁止发送）',
			119	=> '用户已过期',
			120	=> '违反放盗用策略(日发限制) --自定义添加',
			121	=> '必填参数。是否需要状态报告，取值true或false',
			122	=> '5分钟内相同账号提交相同消息内容过多',
			123	=> '发送类型错误',
			124	=> '白模板匹配错误',
			125	=> '匹配驳回模板，提交失败',
			126	=> '审核通过模板匹配错误',
		];

		return isset($map[$code]) ? $map[$code] : '未知错误';
	}

	private $error;
	private $config;
	private $logger;
}
