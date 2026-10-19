<?php
/**
 * QQ登录
 * http://connect.qq.com/
 * 
 * @author suxh
 */
class QqTOAuth extends LogicModel
{
	public function __construct($appId, $appKey, $siteType = 'pc') {
		$this->appId = $appId;
		$this->appKey = $appKey;
		$this->siteType = $siteType;

		if ($this->siteType == 'pc') {
			$this->authorizeUrl = 'https://graph.qq.com/oauth2.0/authorize';
			$this->tokenUrl = 'https://graph.qq.com/oauth2.0/token';
			$this->meUrl = 'https://graph.qq.com/oauth2.0/me';
		}
		else {
			$this->authorizeUrl = 'https://graph.z.qq.com/moc2/authorize';
			$this->tokenUrl = 'https://graph.z.qq.com/moc2/token';
			$this->meUrl = 'https://graph.z.qq.com/moc2/me';
		}
	}
	
	public function getAuthorizeUrl($callbackUrl, $state) {
		$params = array(
			'response_type'	=>'code',
			'client_id'		=>$this->appId,
			'redirect_uri'	=>$callbackUrl,
			'state'			=>$state,
			'scope'			=>'get_user_info'
		);
		$query = http_build_query($params);
		
		return "{$this->authorizeUrl}?{$query}";
	}
	
	public function getAccessToken($code, $redirectUri) {
		$params = array();
		$params['grant_type'] 	= 'authorization_code';
		$params['client_id'] 	= $this->appId;
		$params['redirect_uri']	= $redirectUri;
		$params['client_secret']= $this->appKey;
		$params['code']			= $code;

        $response = $this->curlGet($this->tokenUrl, $params);
        if($response == false) {
        	return false;
        }
        
        if (strpos($response, 'callback') !== false) {
        	$lpos = strpos($response, "(");
        	$rpos = strrpos($response, ")");
        	$response  = substr($response, $lpos + 1, $rpos - $lpos -1);
        	$msg = json_decode($response);
        	if (isset($msg->error)) {
        		return $this->setError('['.$msg->error.']'.$msg->error_description);
        	}
        }
         
        $params = array();
        parse_str($response, $params);
        
        $object = new stdClass();
        $object->accessToken = $params['access_token'];
        $object->expiresIn 	 = $params['expires_in'];
        $object->refreshToken= $params['refresh_token'];
        
        return $object;
	}
	
	public function getOpenId($accessToken) {
		$params = array();
		$params['access_token'] = $accessToken;
		
    	$response  = $this->curlGet($this->meUrl, $params);
		if($response == false) {
			return false;
		}
		
		if (strpos($response, 'callback') !== false) {
	        $lpos = strpos($response, "(");
	        $rpos = strrpos($response, ")");
	        $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
	    }

		$user = json_decode($response);
	    if (isset($user->error)) {
	       return $this->setError('['.$user->error.']'.$user->error_description);
	    }
	    
	    return $user->openid;
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
	
	private $siteType = 'pc';
	
	/**
	 * @var authorize地址
	 */
	private $authorizeUrl;
	private $tokenUrl;
	private $meUrl;
}