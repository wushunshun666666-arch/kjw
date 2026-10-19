<?php
/**
 * 新浪微博登录
 * 申请接入地址：http://open.weibo.com
 * 
 * @author zhenglh
 * @package
 */
class SaeAuthComponent extends LogicModel
{
	/**
	 * 
	 * @var string app key
	 */
	public $appId;
	/**
	 * @var string app secret
	 */
	public $appKey;
	
	public function init() {
		Yii::app()->getSession()->open();
	}
	
	public function getAuthorizeUrl($callbackUrl) {
		$state = md5(uniqid(rand(), true));
		$_SESSION['state'] = $state;
		
		$o = new SaeTOAuthV2($this->appId, $this->appKey);

		return $o->getAuthorizeURL($callbackUrl, 'code', $state);
	}
	
	public function getCode() {
		$state = Yii::app()->request->getParam('state');
		$code = Yii::app()->request->getParam('code');
	
		if(!(isset($_SESSION['state']) && $state == $_SESSION['state'])) {
			return $this->setError('授权失败');
		}
		
		return $code;
	}
	
	public function getAccessToken($code, $redirectUri){
		$o = new SaeTOAuthV2($this->appId, $this->appKey);
	
		$keys = array();
		$keys['code'] = $code;
		$keys['redirect_uri'] = $redirectUri;
		try {
			$token = $o->getAccessToken('code', $keys) ;
		} catch (OAuthException $e) {
			return $this->setError($e->getMessage());
		}
		
		$object = new stdClass();
        $object->accessToken = $token['access_token'];
        $object->remindIn= $token['remind_in'];
        $object->remindIn= $token['remind_in'];
        $object->expiresIn= $token['expires_in'];
        $object->uid= $token['uid'];
        
        return $object;
	}
	
	public function getUser($token) {
		$accessToken = $token->accessToken;
		$uid = $token->uid;
		
		$client = new SaeTClientV2($this->appId, $this->appKey, $accessToken);
		
		$user = $client->show_user_by_id($uid);
		
		if($user == false) {
			return $this->setError('获取用户信息失败');
		}
		
		$object = new stdClass();
		$object->openId = $uid;
		$object->nickname = $user['name'];
		
		return $object;
	}
}