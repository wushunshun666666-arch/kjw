<?php
/**
 * 微信登录
 * 
 * @author suxh
 */
class WeixinTOAuth extends LogicModel
{
	public function __construct($appId, $appKey) {
		$this->appId = $appId;
		$this->appKey = $appKey;

		$this->authorizeUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize';
		$this->tokenUrl = 'https://api.weixin.qq.com/sns/oauth2/access_token';
		$this->userInfoUrl = 'https://api.weixin.qq.com/sns/userinfo';
	}
	
	public function getAuthorizeUrl($callbackUrl, $state) {
		$params = array(
			'appid'			=>$this->appId,
			'redirect_uri'	=>$callbackUrl,
			'response_type'	=>'code',
			'scope'			=>'snsapi_userinfo',
			'state'			=>$state,
		);
		$query = http_build_query($params);
		
		return "{$this->authorizeUrl}?{$query}#wechat_redirect";
	}
	
	public function getAccessToken($code, $redirectUri) {
		$params = array();
		$params['appid']		= $this->appId;
		$params['secret'] 		= $this->appKey;
		$params['code']			= $code;
		$params['grant_type'] 	= 'authorization_code';

        $response = $this->curlGet($this->tokenUrl, $params);
        if($response == false) {
        	return false;
        }
        
        $response = json_decode($response);
        if (isset($response->errcode)) {
        	return $this->setError($response->errmsg);
        }
        
        $object = new stdClass();
        $object->accessToken = $response->access_token;
        $object->expiresIn 	 = $response->expires_in;
        $object->refreshToken= $response->refresh_token;
        $object->openId		 = $response->openid;
        $object->scope		 = $response->scope;
        
        return $object;
	}
	
	public function getUserInfo($accessToken, $userId) {
		$params = array();
		$params['access_token'] = $accessToken;
		$params['openid'] 		= $userId;
		$params['lang'] 		= 'zh_CN';
		
		$response  = $this->curlGet($this->userInfoUrl, $params);
		if($response == false) {
			return false;
		}

		$response = json_decode($response);
		if (isset($response->errcode)) {
			return $this->setError($response->errmsg);
		}
	    
	    $object = new stdClass();
	    $object->openId		= $response->openid;
	    $object->nickname 	= $response->nickname;
	    $object->sex 		= $response->sex;
	    $object->province	= $response->province;
	    $object->city 		= $response->city;
	    $object->country 	= $response->country;
	    $object->headImgUrl = $response->headimgurl;
	    $object->privilege 	= $response->privilege;
	    
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
	/**
	 * @var authorize地址
	 */
	private $authorizeUrl;
	private $tokenUrl;
	private $userInfoUrl;
}