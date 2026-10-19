<?php
/**
 * Curl.php
 * @author suxh
 */

namespace mylibs;

class Curl
{
	public function __construct() {
		if(!extension_loaded('curl')) {
			throw new \Exception('Curl requires PHP curl extension to be loaded.');
		}
	}

	public function setTimeout($seconds) {
		$this->timeout = $seconds;
	}

	/**
	 * get
	 * @param string $url 地址
	 * @param array $params get参数
	 * @return string 失败返回false，成功返回具体内容
	 */
	public function get($url, $params = null) {
		if ($params) {
			$query = http_build_query($params);
			$url = $url . '?' . $query;
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$result = curl_exec($ch);

		if($result === false) {
			$rs = curl_error($ch);
			return $this->setError('获取数据失败：'.$rs);
		}

		return $result;
	}

	/**
	 * post
	 * @param string $url 地址
	 * @param array $data post数据
	 * @return string 失败返回false，成功返回具体内容
	 */
	public function post($url, $data = null) {
		if (is_array($data)) {
			$data = http_build_query($data);
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//设置是否返回信息
		curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//POST数据
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

		$result = curl_exec($ch);

		if($result === false) {
			$rs = curl_error($ch);
			return $this->setError('获取数据失败:'.$rs);
		}

		return $result;
	}

	public function getError() {
		return $this->error;
	}

	protected function setError($error) {
		$this->error = $error;
		return false;
	}

	private $error = null;
	private $timeout = 10;
}
