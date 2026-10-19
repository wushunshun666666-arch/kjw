<?php
/**
 * BaiduLbs.php
 * @author suxh
 */

namespace mylibs\lbs;

class BaiduLbs
{
	public function __construct($ak, $logger=null) {
		$this->ak = $ak;
		$this->logger = $logger;
		
	}

	public function getError() {
		return $this->error;
	}
	
	public function geocoding($lat, $lng) {
		$url = 'http://api.map.baidu.com/geocoder/v2/?';
		$params = array(
			'ak'		=>$this->ak,
			'callback'	=>'',
			'location'	=>"$lat,$lng",
			'output'	=>'json',
			'pois'		=>0,
		);
		$url .= http_build_query($params);
		
		$res = $this->sendGetCommand($url);
		if ($res === false) {
			return false;
		}
		if ($res->status != 0) {
			return $this->setError('逆地理编码失败：'.$res->status);
		}
		
		return $res->result;
	}

	protected function setError($error) {
		$this->error = $error;
		$this->log($error, 'ERROR');
		return false;
	}

	protected function log($data, $level='DEBUG') {
		if ($this->logger) {
			$date = date('Y-m-d H:i:s');
			$this->logger->log("[$date]\r\n".$data, $level);
		}
	}
	
	private function sendGetCommand($url) {
		$result = $this->httpGet($url);
		if ($result === false) {
			return false;
		}
		
		$json = json_decode($result);
		if (!$json) {
			return $this->setError('JSON格式错误');
		}
		if (isset($json->errcode) && $json->errcode > 0) {
			return $this->setError($json->errmsg."($json->errcode)");
		}
		
		return $json;
	}

	/**
	 * GET 请求
	 * @param string $url
	 * @return string 
	 */
	private function httpGet($url){
		$curl = curl_init();
		if(stripos($url,'https://')!==false) {
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
		
		$response = curl_exec($curl);
		if ($response === false) {
			$error = curl_error($curl);
			$errno = curl_errno($curl);
			
			$this->log("REQUEST:\r\n{$url}\r\n");
			return $this->setError($error."($errno)");
		}
		
		$status = curl_getinfo($curl);
		
		$this->log("REQUEST:\r\n{$url}\r\nRESPONSE:\r\n{$response}\r\n");
		
		if(intval($status['http_code'])!=200){
			return $this->setError($status['http_code']);
		}
		
		return $response;
	}
	
	/**
	 * @var string
	 */
	private $ak;
	/**
	 * @var IBaiduLbsLogger
	 */
	private $logger;
	/**
	 * @var string
	 */
	private $error;
}
