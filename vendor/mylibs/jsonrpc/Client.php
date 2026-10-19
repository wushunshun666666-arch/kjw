<?php
/**
 * Client.php
 * @author suxh
 */

namespace mylibs\jsonrpc;

class Client
{
	public function __construct($url, $logger = null, $signKey=null) {
		$this->url = $url;
		if (!$logger) {
			$logger = new Logger();
		}

		$this->logger = $logger;
		$this->signKey = $signKey;
	}

	public function __call($name, $arguments) {
		return $this->call($name, $arguments);
	}

	public function call($method, $params, $id=null) {
		if ($id === null) {
			$id = $this->generateId();
		}

		return $this->doCall($method, $params, $id);
	}

	public function callNotify($method, $params) {
		return $this->doCall($method, $params);
	}

	protected function generateId() {
		$date = date('ymd');
		$rand = mt_rand(10000000, 99999999);
		return $date.$rand;
	}

	protected function doCall($method, $params, $id=null) {
		$request = [
			'jsonrpc' => '2.0',
			'method' => $method,
			'params' => $params,
		];
		if ($id) {
			$request['id'] = $id;
		}

		$data = json_encode($request);

		$url = $this->url;
		if (!empty($this->signKey)) {
			$sign = md5($data.$this->signKey);
			$url = $url.'?sign='.$sign;
		}

		$response = $this->httpPost($url, $data);
		if ($response === false) {
			return false;
		}

		if ($response === '') {
			return $this->setError('Response is empty');
		}

		$response = $this->decode($response);
		if ($response === false) {
			return false;
		}

		if (property_exists($response, 'error')) {
			$error = $response->error;
			$message = $error->message."({$error->code})";
			return $this->setError($message);
		}
		else if ($response->id != $id) {
			return $this->setError('Mismatched JSON-RPC IDs');
		}
		else if (property_exists($response, 'result')) {
			return $response->result;
		} else {
			return $this->setError('Invalid JSON-RPC response');
		}
	}

	private function decode($json) {
		$decode = json_decode($json);
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				break;
			case JSON_ERROR_DEPTH:
				return $this->setError('The maximum stack depth has been exceeded.');
			case JSON_ERROR_CTRL_CHAR:
				return $this->setError('Control character error, possibly incorrectly encoded.');
			case JSON_ERROR_SYNTAX:
				return $this->setError('Syntax error.');
			case JSON_ERROR_STATE_MISMATCH:
				return $this->setError('Invalid or malformed JSON.');
			case JSON_ERROR_UTF8:
				return $this->setError('Malformed UTF-8 characters, possibly incorrectly encoded.');
			default:
				return $this->setError('Unknown JSON decoding error.');
		}

		return $decode;
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
	 * 请求
	 * @param string $url
	 * @param string $data
	 */
	private function httpPost($url, $data) {
		$this->logger->logRequest($data, $url);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);	//设置是否返回信息
		curl_setopt($ch, CURLOPT_POST, 1);				//设置为POST方式
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);		//POST数据
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);

		$response = curl_exec($ch);

		if($response === false) {
			$error = curl_error($ch);
			return $this->setError('获取数据失败:'.$error);
		}

		$status = curl_getinfo($ch);

		$this->logger->logResponse($response);

		if(intval($status['http_code'])!=200){
			return $this->setError($status['http_code']);
		}

		return $response;
	}

	private $url;
	private $signKey;

	/**
	 * @var Logger 日志记录
	 */
	protected $logger;

	/**
	 * @var string 错误信息
	 */
	private $error;
}
