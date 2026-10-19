<?php
/**
 * WeixinAuthComponent.php
 * @author suxh
 * @package
 */

class WeixinAuthComponent extends LogicModel
{
	/**
	 * @var string APP ID
	 */
	public $appId;
	/**
	 * @var string APP KEY
	 */
	public $appKey;

	/**
	 * (non-PHPdoc)
	 * @see CApplicationComponent::init()
	 */
	public function init() {
		$this->oauth = new WeixinTOAuth($this->appId, $this->appKey);

		Yii::app()->getSession()->open();
	}

	public function getAuthorizeUrl($callbackUrl) {
		$state = md5(uniqid(rand(), true));
		$_SESSION['state'] = $state;

		return $this->oauth->getAuthorizeUrl($callbackUrl, $state);
	}

	public function getCode() {
		$state = Yii::app()->request->getParam('state', null);
		$code = Yii::app()->request->getParam('code', null);

		if ($code == null) {
			return $this->setError('授权失败');
		}
		if (!(isset($_SESSION['state']) && $state == $_SESSION['state'])) {
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
		$openId = $token->openId;

		// 获取用户信息
		$res = $this->oauth->getUserInfo($accessToken, $openId);
		if ($res == false) {
			return $this->addErrors($this->oauth->getErrors());
		}

		return $res;
	}

	private $oauth;
}

// End of file