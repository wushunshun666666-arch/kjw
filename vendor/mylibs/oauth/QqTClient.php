<?php
/**
 * QQ Open API
 * @author suxh
 */

class QqTClient extends LogicModel
{
	public function __construct($appId, $appKey) {
		$this->appId = $appId;
		$this->appKey = $appKey;
		$this->userUrl = 'https://graph.qq.com/user/get_user_info';
	}
	
	public function getUserInfo($accessToken, $openid) {
		$params = array();
		$params['access_token'] = $accessToken;
		$params['oauth_consumer_key'] = $this->appId;
		$params['openid'] = $openid;
	
		$response  = $this->curlGet($this->userUrl, $params);
		if($response == false) {
			return false;
		}
	
		$user = json_decode($response);
		if ($user->ret != 0) {
			return $this->setError('['.$user->ret.']'.$user->msg);
		}
		 
		$object = new stdClass();
		$object->nickname 		= $user->nickname;
		$object->figureurl 		= $user->figureurl;
		$object->figureurl1 	= $user->figureurl_1;
		$object->figureurl2 	= $user->figureurl_2;
		$object->figureurlQq1 	= $user->figureurl_qq_1;
		$object->figureurlQq2 	= $user->figureurl_qq_2;
		$object->gender 		= $user->gender;
		$object->isYellowVip 	= $user->is_yellow_vip;
		$object->vip 			= $user->vip;
		$object->yellowVipLevel = $user->yellow_vip_level;
		$object->level 			= $user->level;
		$object->isYellowYearVip= $user->is_yellow_year_vip;
		 
		return $object;
	}
	
	/**
	 * 获取返回信息
	 * @param string $url
	 * @param array $params
	 * @return string 失败返回false，成功返回具体内容
	 */
	private function curlGet($url, $params = null) {
		$curl = new Curl();
		$result = $curl->get($url, $params);
		if ($result === false) {
			return $this->addErrors($curl->getErrors());
		}
	
		return $result;
	}
	
	/**
	 * @var string APP ID
	 */
	private $appId;
	/**
	 * @var string APP KEY
	 */
	private $appKey;
	
	private $userUrl;
}