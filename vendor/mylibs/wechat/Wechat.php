<?php
/**
 * Wechat.php
 * @author suxh
 * @package
 */

namespace mylibs\wechat;
class Wechat
{
	/**
	 * 构造函数
	 * @param WechatConfig $config
	 * @param IWechatLogger $logger
	 */
	public function __construct(WechatConfig $config, $logger = null) {
		$this->config = $config;
		$this->logger = $logger;
	}

	public function getError() {
		return $this->error;
	}

	/**
	 * 接收消息
	 * @return WechatMessage
	 */
	public function getMessage() {
		if ($this->message == null) {
			$message = new WechatMessage($this->logger);
			if (!$message->receive($this->config->token)) {
				return $this->setError($message->getError());
			}

			$this->message = $message;
		}

		return $this->message;
	}

	public function reflashAccessToken() {
		$appid = $this->config->appid;
		$appsecret = $this->config->appsecret;

		$url = $this->config->authUrl.'appid='.$appid.'&secret='.$appsecret;
		return $this->sendGetCommand($url);
	}

	public function getAccessToken() {
		if ($this->accessToken == null) {
			$accessToken = $this->reflashAccessToken();
			if (!$accessToken) {
				return false;
			}

			$this->accessToken = $accessToken->access_token;
		}

		return $this->accessToken;
	}

	/**
	 * 客服接口-发消息
	 * @param unknown $openid
	 * @param unknown $message
	 */
	public function sendUserMessage($openid, $message) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->sendMessageUrl;
		$data = [
			'touser'	=> $openid,
			'msgtype'	=> 'text',
			'text'		=> [
				'content' => $message,
			],
		];
		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 客服接口-发图文消息
	 * @param string $openid
	 * @param array $news
	 * array(
	 *  	[0]=>array(
	 *  		'title'=>'title',
	 *  		'description'=>'description',
	 *  		'picurl'=>'PIC_URL',
	 *  		'url'=>'URL'
	 *  	),
	 *  	[1]=>....
	 *  )
	 */
	public function sendUserNews($openid, $news) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->sendMessageUrl;
		$data = [
			'touser'	=> $openid,
			'msgtype'	=> 'news',
			'news'		=> [
				'articles' => $news,
			],
		];
		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 模板消息-发送消息
	 * @param string $openid		用户openid
	 * @param string $templateId	模板消息ID
	 * @param array  $params
	 *  [
	 *  	'first'		=> ['value'=>'first value', 'color'=>'first color#FFFFFF'],
	 *  	'keynote1'	=> ['value'=>'keynote1 value', 'color'=>'first color#FFFFFF'],
	 *  	'keynote2'	=> ['value'=>'keynote2 value', 'color'=>'first color#FFFFFF'],
	 *  	...
	 *  	'remark'	=> ['value'=>'remark value', 'color'=>'first color#FFFFFF'],
	 *  ]
	 * @param string $goUrl			模板链接
	 */
	public function sendTemplateMessage($openid, $templateId, $params, $goUrl=null) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->sendTemplateMessageUrl;
		$data = [
			'touser'		=> $openid,
			'template_id'	=> $templateId,
			'data'			=> $params,
			'url'			=> $goUrl
		];

		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 上传媒体文件
	 * @param string $filePath 文件路径
	 * @param string $fileType 文件类型 'image/jpg', 'image/png,
	 * @param string $type     媒体文件类型 - 图片（image）、语音（voice）、视频（video）和缩略图（thumb，主要用于视频与音乐格式的缩略图）
	 */
	public function uploadMedia($filePath, $fileType, $type) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->mediaUploadUrl;
		$data = [];
		$files = [
			'media' => ['path' => realpath($filePath), 'type' => $fileType]
		];
		return $this->sendPostCommand($url, $data, $accessToken, ['type' => $type], $files);
	}

	/**
	 * 上传图文消息
	 * @param array $items
	 */
	public function uploadNews($items) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->uploadNewsUrl;
		$data = ['articles' => $items];

		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 群发图文消息
	 * @param string  $mediaId
	 * @param integer $tagId
	 */
	public function sendAllNews($mediaId, $tagId=null) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->sendMessageToAllUrl;
		$data = [
			'mpnews'	=> ['media_id' => $mediaId],
			'msgtype'	=> 'mpnews',
			'filter'	=> ['is_to_all' => true]
		];
		if ($tagId) {
			$data['filter'] = [
				'is_to_all'	=> false,
				'tag_id'	=> $tagId
			];
		}

		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 群发文本消息
	 * @param string  $content
	 * @param integer $tagId
	 */
	public function sendAllContent($content, $tagId=null) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->sendMessageToAllUrl;
		$data = [
			'text'		=> ['content' => $content],
			'msgtype'	=> 'text',
			'filter'	=> ['is_to_all' => true]
		];
		if ($tagId) {
			$data['filter'] = [
				'is_to_all'	=> false,
				'tag_id'	=> $tagId
			];
		}

		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 关注消息
	 */
	public function getSubscribeMessage() {
		return $this->config->subscribeMessage;
	}

	/**
	 * 默认消息
	 */
	public function getDefaultReplay() {
		return $this->config->defaultReplay;
	}

	/**
	 * 个性化菜单
	 */
	public function createConditionalMenu($name, $matchRule) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->menuConditionalUrl;
		$menu = $this->config->$name;
		return $this->createMenu($url, $menu, $matchRule);
	}

	/**
	 * 默认菜单
	 */
	public function createNormalMenu() {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->menuCreateUrl;
		$menu = $this->config->normalMenu;
		return $this->createMenu($url, $menu);
	}

	/**
	 * 创建菜单
	 * @param array $items 菜单数组数据
	 * @return boolean 返回操作是否成功
	 */
	private function createMenu($url, $items, $matchRul=[]) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		// 预处理页面授权
		foreach ($items as &$item) {
			if (isset($item['sub_button'])) {
				foreach ($item['sub_button'] as &$subItem) {
					$subItem['auth'] = isset($subItem['auth']) ? $subItem['auth'] : false;
					if ($subItem['type'] == 'view' && $subItem['auth']) {
						$state = isset($subItem['state']) ? $subItem['state'] : '';
						unset($subItem['state']);
						$subItem['url'] = $this->silentOauthUrl($subItem['url'], $state);
					}
					unset($subItem['auth']);
				}
			} else {
				$item['auth'] = isset($item['auth']) ? $item['auth'] : false;
				if ($item['type'] == 'view' && $item['auth']) {
					$state = isset($item['state']) ? $item['state'] : '';
					unset($item['state']);
					$item['url'] = $this->silentOauthUrl($item['url'], $state);
				}
				unset($item['auth']);
			}
		}

		$menu = ['button'=>$items];
		if (!empty($matchRul)) {
			$menu['matchrule'] = $matchRul;
		}

		if (!$this->sendPostCommand($url, $menu, $accessToken)) {
			return false;
		}

		return true;
	}

	/**
	 * 获取菜单
	 * @return stdClass
	 */
	public function getMenu(){
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->menuGetUrl.'access_token='.$accessToken;
		return $this->sendGetCommand($url);
	}

	/**
	 * 删除菜单
	 * @return boolean
	 */
	public function deleteMenu(){
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->menuDeleteUrl.'access_token='.$accessToken;
		if (!$this->sendGetCommand($url)) {
			return false;
		}

		return true;
	}

	/**
	 * 新建分组
	 * @param string $name
	 */
	public function creatGroup($name) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->groupCreateUrl;
		$data = ['group' => [
			'name' => $name
		]];
		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 删除分组
	 * @param unknown $id
	 * @return boolean|boolean|mixed
	 */
	public function deleteGroup($id) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->groupDeleteUrl;
		$data = ['group' => [
			'id' => $id
		]];
		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 查询所有分组
	 */
	public function getGroups() {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->groupGetUrl.'access_token='.$accessToken;
		return $this->sendGetCommand($url);
	}

	/**
	 * 移动分组
	 * @param string  $openid
	 * @param integer $groupId
	 */
	public function updateMemberGroup($openid, $groupId) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->groupMemberUpdateUrl;
		$data = [
			'openid'		=> $openid,
			'to_groupid'	=> $groupId,
		];
		return $this->sendPostCommand($url, $data, $accessToken);
	}

	/**
	 * 获取关注者详细信息
	 * @param string $openid
	 * @return array
	 */
	public function getUserInfo($openid) {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->userInfoUrl.'access_token='.$accessToken.'&openid='.$openid;
		return $this->sendGetCommand($url);
	}

	/**
	 * 获取网页授权 access token
	 * @param string $code
	 */
	public function getOauthToken($code) {
		$appid 	= $this->config->appid;
		$secret	= $this->config->appsecret;

		$url = $this->config->oauthTokenUrl.'appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';
		return $this->sendGetCommand($url);
	}

	/**
	 * 刷新网页授权 access token
	 * @param string $refreshToken
	 */
	public function refreshOauthToken($refreshToken) {
		$appid 	= $this->config->appid;
		$secret	= $this->config->appsecret;

		$url = $this->config->oauthRefreshUrl.'appid='.$appid.'&refresh_token'.$refreshToken.'&grant_type=refresh_token';
		return $this->sendGetCommand($url);
	}

	/**
	 * 网页授权回调-静默跳转
	 * @param string $url 授权后回调URL，不需要urlencode
	 * @param string $state 额外参数
	 */
	public function silentOauthUrl($redirectUrl, $state='') {
		$params = [
			'appid'			=> $this->config->appid,
			'redirect_uri'	=> $redirectUrl,
			'response_type'	=> 'code',
			'scope'			=> WechatConfig::BASE_SCOPE,
			'state'			=> $state
		];

		return $this->getWebAuthUrl($params);
	}

	/**
	 * 网页授权回调-授权跳转
	 * @param string $url 授权后回调URL，不需要urlencode
	 * @param string $state 额外参数
	 */
	public function oauthUrl($redirectUrl, $state='') {
		$params = [
			'appid'			=> $this->config->appid,
			'redirect_uri'	=> $redirectUrl,
			'response_type'	=> 'code',
			'scope'			=> WechatConfig::USERINFO_SCOPE,
			'state'			=> $state
		];

		return $this->getWebAuthUrl($params);
	}

	public function guestUrl() {
		return $this->config->guestUrl;
	}

	public function distributionUrl() {
		return $this->config->distributionUrl;
	}

	public function groupId($type='distribution') {
		return $this->config->groupId[$type];
	}

	/**
	 * JS接口的临时票据
	 */
	public function getJsApiTicket() {
		$accessToken = $this->getAccessToken();
		if (!$accessToken) {
			return false;
		}

		$url = $this->config->jsApiTicketUrl.$accessToken;
		return $this->sendGetCommand($url);
	}

	/**
	 * 注册JS接口
	 * @param unknown $ticket
	 * @param unknown $url
	 */
	public function signJsApi($ticket, $url) {
		$timestamp = time();
		$nonceStr = $this->nonceStr();

		$string = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
		$signature = sha1($string);

		return [
			"appId"		=> $this->config->appid,
			"nonceStr"	=> $nonceStr,
			"timestamp"	=> $timestamp,
			"url"		=> $url,
			"signature"	=> $signature,
		];
	}

	private function nonceStr($length = 16) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$str = "";
		for ($i = 0; $i < $length; $i++) {
			$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}

		return $str;
	}

	private function getWebAuthUrl($params) {
		$params['state'] .= '{mancando}';
		$url = $this->config->oauthAuthorizeUrl;
		$url .= http_build_query($params).'#wechat_redirect';
		return $url;
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
		if (isset($json->errcode) && $json->errcode != 0) {
			return $this->setError($json->errmsg."({$json->errcode})");
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

	protected function sendPostCommand($url, $data, $accessToken, $extraUrlParams=[], $files=[]) {
		$url .= 'access_token='.$accessToken;
		foreach ($extraUrlParams as $name=>$param) {
			$url .= "&{$name}={$param}";
		}

		$data = empty($files) ? self::json_encode($data) : $data;
		$result = $this->httpPost($url, $data, $files);

		if (!$result) {
			return false;
		}

		$json = json_decode($result);
		if (!$json) {
			return $this->setError('JSON格式错误');
		}
		if (isset($json->errcode) && $json->errcode != 0) {
			return $this->setError($json->errmsg."({$json->errcode})");
		}

		return $json;
	}

	/**
	 * POST 请求
	 * @param string $url
	 * @param array $param
	 * @return string
	 */
	protected function httpPost($url, $param, $files=[]){
		$curl = curl_init();
		if(stripos($url,'https://')!==false){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}

		foreach ($files as $name=>$item) {
			$param[$name] = new \CURLFile($item['path'], $item['type']);
		}

		$postStr = is_string($param) ? $param : json_encode($param);

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt($curl, CURLOPT_POST,true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $param);

		$response = curl_exec($curl);
		if ($response === false) {
			$error = curl_error($curl);
			$errno = curl_errno($curl);
			$this->log("REQUEST:\r\n{$url}\r\n{$postStr}\r\n");
			return $this->setError($error."($errno)");
		}

		$status = curl_getinfo($curl);

		$this->log("REQUEST:\r\n{$url}\r\n{$postStr}\r\nRESPONSE:\r\n{$response}\r\n");

		if(intval($status['http_code'])!=200){
			return $this->setError($status['http_code']);
		}

		return $response;
	}

	/**
	 * 微信api不支持中文转义的json结构
	 * @param array $arr
	 */
	public static function json_encode($arr) {
		$parts = array ();
		$is_list = false;
		//Find out if the given array is a numerical array
		$keys = array_keys ( $arr );
		$max_length = count ( $arr ) - 1;
		if (($keys [0] === 0) && ($keys [$max_length] === $max_length )) { //See if the first key is 0 and last key is length - 1
			$is_list = true;
			for($i = 0; $i < count ( $keys ); $i ++) { //See if each key correspondes to its position
				if ($i != $keys [$i]) { //A key fails at position check.
					$is_list = false; //It is an associative array.
					break;
				}
			}
		}
		foreach ( $arr as $key => $value ) {
			if (is_array ( $value )) { //Custom handling for arrays
				if ($is_list)
					$parts [] = self::json_encode ( $value ); /* :RECURSION: */
				else
					$parts [] = '"' . $key . '":' . self::json_encode ( $value ); /* :RECURSION: */
			} else {
				$str = '';
				if (! $is_list)
					$str = '"' . $key . '":';
				//Custom handling for multiple data types
				if (is_numeric ( $value ) && $value<2000000000)
					$str .= $value; //Numbers
				elseif ($value === false)
				$str .= 'false'; //The booleans
				elseif ($value === true)
				$str .= 'true';
				else
					$str .= '"' . addslashes ( $value ) . '"'; //All other things
				// :TODO: Is there any more datatype we should be in the lookout for? (Object?)
				$parts [] = $str;
			}
		}
		$json = implode ( ',', $parts );
		if ($is_list)
			return '[' . $json . ']'; //Return numerical JSON
		return '{' . $json . '}'; //Return associative JSON
	}

	/**
	 * @var WechatConfig
	 */
	private $config;
	/**
	 * @var IWechatLogger
	 */
	private $logger;
	/**
	 * @var WechatMessage
	 */
	private $message = null;
	/**
	 * @var string
	 */
	private $error;
	/**
	 * @var string
	 */
	private $accessToken = null;
}

// End of file
