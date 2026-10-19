<?php
/**
 * TaobaoAuth.php
 * @author suxh
 * @package
 */

class TaobaoAuth extends LogicModel
{
	public function __construct($config = null) {
		if ($config === null) {
			$config = new Configure();
			$config->loadCommonConfig('topclient');
		}
		
		$this->clientId = $config['appkey'];
		$this->secretKey = $config['secretKey'];
		$this->oauthUrl = $config['oauthUrl'];
		$this->tokenUrl = $config['tokenUrl'];
	}
	
	public function authorize($callbackUrl, $state=null) {
		$callbackUrl = urlencode($callbackUrl);
		$url = $this->oauthUrl."?response_type=code&client_id={$this->clientId}&redirect_uri={$callbackUrl}&state={$state}";
		
		header("Location:$url");
	}
	
	public function getCode() {
		$error = Yii::app()->request->getParam('error', null);
		if ($error) {
			$error_description = Yii::app()->request->getParam('error_description', null);
			return $this->setError('授权失败：'.$error_description);
		}
		
		$code = Yii::app()->request->getParam('code', null);
		$state = Yii::app()->request->getParam('state', null);
		
		$object = new stdClass();
		$object->code = $code;
		$object->state = $state;
		
		return $object;
	}
	
	public function getToken($code, $redirectUri) {
		$data = array(
			'code'=>$code,
			'grant_type'=>'authorization_code',
			'client_id'=>$this->clientId,
			'client_secret'=>$this->secretKey,
			'redirect_uri'=>$redirectUri,
		);
		$res = $this->curlPost($this->tokenUrl, $data);
		if ($res === false) {
			return false;
		}
		
		$res = json_decode($res);
		
		$object = new stdClass();
		$object->accessToken = $res->access_token;
		$object->expires 	 = $res->expires_in;
		$object->userNick 	 = urldecode($res->taobao_user_nick);
		$object->userId 	 = $res->taobao_user_id;
		$object->r1Expires 	 = $res->r1_expires_in;
		$object->r2Expires 	 = $res->r2_expires_in;
		$object->w1Expires 	 = $res->w1_expires_in;
		$object->w2Expires 	 = $res->w2_expires_in;
		$object->refreshToken = isset($res->refresh_token)?$res->refresh_token:null;
		$object->reExpires 	= isset($res->re_expires_in)?$res->re_expires_in:null;
		$object->subUserId  = isset($res->sub_taobao_user_id)?$res->sub_taobao_user_id:null;
		$object->subUserNick= isset($res->sub_taobao_user_nick)?$res->sub_taobao_user_nick:null;

		return $object;
	}
	
	public function refreshToken($refreshToken, $state = null, $view = 'web') {
		$data = array(
			'client_id'=>$this->clientId,
			'client_secret'=>$this->secretKey,
			'grant_type'=>'refresh_token',
			'refresh_token'=>$refreshToken,
			'state'=>$state,
			'view'=>$view
		);
		$res = $this->curlPost($this->tokenUrl, $data);
		if ($res === false) {
			return false;
		}
		
		$res = json_decode($res);
		
		if ( !isset($res->access_token) ) {
			$this->setError('命令返回失败:'.json_encode($res));
			return false;
		}
		
		$object = new stdClass();
		$object->accessToken= $res->access_token;
		$object->expires 	= $res->expires_in;
		$object->userNick 	= urldecode($res->taobao_user_nick);
		$object->userId 	= $res->taobao_user_id;
		$object->r1Expires 	= $res->r1_expires_in;
		$object->r2Expires 	= $res->r2_expires_in;
		$object->w1Expires 	= $res->w1_expires_in;
		$object->w2Expires 	= $res->w2_expires_in;
		$object->refreshToken = isset($res->refresh_token)?$res->refresh_token:null;
		$object->reExpires 	= isset($res->re_expires_in)?$res->re_expires_in:null;
		$object->subUserId  = isset($res->sub_taobao_user_id)?$res->sub_taobao_user_id:null;
		$object->subUserNick= isset($res->sub_taobao_user_nick)?$res->sub_taobao_user_nick:null;

		return $object;
	}
	
	/**
	 * curl访问并获取结果
	 * @param string $url
	 * @return string 失败返回false，成功返回具体内容
	 */
	private function curlPost($url, $data = null) {
		$curl = new Curl();
		$result = $curl->post($url, $data);
		if ($result === false) {
			return $this->addErrors($curl->getErrors());
		}
		
		return $result;
	}
	
	private $clientId = null;
	private $oauthUrl = null;
	private $tokenUrl = null;
	private $secretKey = null;
}

// End of file