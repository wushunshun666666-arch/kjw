<?php
/**
 * QQAuthComponent.php
 * @author suxh
 * @package
 */

class QQAuthComponent extends LogicModel
{
	/**
	 * @var string APP ID
	 */
	public $appId;
	/**
	 * @var string APP KEY
	 */
	public $appKey;
	
	public $siteType = null;
	
	/**
	 * (non-PHPdoc)
	 * @see CApplicationComponent::init()
	 */
	public function init() {
		if ($this->siteType == null) {
			$detect = new MobileDetect();
			$this->siteType = ($detect->isMobile() || $detect->isTablet())? 'wap':'pc';
		}
		
		$this->oauth = new QqTOAuth($this->appId, $this->appKey, $this->siteType);
		$this->client= new QqTClient($this->appId, $this->appKey);
		
		Yii::app()->getSession()->open();
	}
	
	public function getAuthorizeUrl($callbackUrl) {
		$state = md5(uniqid(rand(), true));
		$_SESSION['state'] = $state;
	
		return $this->oauth->getAuthorizeUrl($callbackUrl, $state);
	}
	
	public function getCode() {
		$msg = Yii::app()->request->getParam('msg', null);
		if ($msg) {
			$code = Yii::app()->request->getParam('code', null);
			return $this->setError("授权失败：[$code]$msg");
		}
		$usercancel = Yii::app()->request->getParam('usercancel', null);
		if ($usercancel) {
			return $this->setError('授权失败：用户取消');
		}
	
		$state = Yii::app()->request->getParam('state');
		$code = Yii::app()->request->getParam('code');
	
		if(!(isset($_SESSION['state']) && $state == $_SESSION['state'])) {
			return $this->setError('授权失败');
		}
		
		return $code;
	}
	
	public function getAccessToken($code, $redirectUri) {
		$res = $this->oauth->getAccessToken($code, $redirectUri);
		if ($res == false) {
			return $this->addErrors($this->oauth->getErrors());
		}
		
		return $res;
	}
	
	public function getUser($token) {
		$accessToken = $token->accessToken;
		
		// 获取用户ID
		$openid = $this->oauth->getOpenId($accessToken);
		if ($openid == false) {
			return $this->addErrors($this->oauth->getErrors());
		}
		
		// 获取用户信息
		$res = $this->client->getUserInfo($accessToken, $openid);
		if ($res == false) {
			return $this->addErrors($this->client->getErrors());
		}
		
		$res->openId = $openid;
		
		return $res;
	}
	
	private $oauth;
	private $client;
}

// End of file