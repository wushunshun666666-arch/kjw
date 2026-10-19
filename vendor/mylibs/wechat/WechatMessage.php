<?php
/**
 * WechatMessage.php
 * @author suxh
 * @package
 */

namespace mylibs\wechat;
class WechatMessage
{
	const TYPE_NONE = 'none';
	const TYPE_TEXT = 'text';
	const TYPE_IMAGE = 'image';
	const TYPE_LOCATION = 'location';
	const TYPE_LINK = 'link';
	const TYPE_EVENT = 'event';
	const TYPE_VOICE = 'voice';
	const TYPE_VIDEO = 'video';

	const TYPE_MUSIC = 'music';
	const TYPE_NEWS = 'news';

	const TYPE_TRANSFER = 'transfer_customer_service';

	public function __construct($logger=null) {
		$this->data = array();
		$this->logger = $logger;
	}

	public function receive($token) {
		$post = file_get_contents("php://input");
		$this->log("Receive message:\r\n$post\r\n");

		if (!$this->checkSignature($token)) {
			return $this->setError('消息签名校验失败');
		}
		if (!empty($post)) {
			$this->data = (array)simplexml_load_string($post, 'SimpleXMLElement', LIBXML_NOCDATA);
		}

		return true;
	}

	/**
	 * @return string 返回错误信息
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @return string 返回消息类型
	 */
	public function getType() {
		return $this->getValue('MsgType', self::TYPE_NONE);
	}

	/**
	 * return string 返回消息ID
	 */
	public function getRevID() {
		return $this->getValue('MsgId');
	}

	public function getEchoStr() {
		return (isset($_GET['echostr']))? $_GET['echostr']:null;
	}

	/**
	 * @return string 返回消息发送者
	 */
	public function getFromUserName() {
		return $this->getValue('FromUserName');
	}

	/**
	 * @return string 返回消息接受者
	 */
	public function getToUserName() {
		return $this->getValue('ToUserName');
	}

	/**
	 * @return string 返回消息发送时间
	 */
	public function getCreateTime() {
		return $this->getValue('CreateTime');
	}

	/**
	 * @return string 返回消息内容正文
	 */
	public function getContent() {
		$content = $this->getValue('Content');
		if ($content === null) {
			//获取语音识别文字内容，需申请开通
			$content = $this->getValue('Recognition');
		}

		return $content;
	}

	/**
	 * @return string 返回接收消息图片
	 */
	public function getPicUrl() {
		return $this->getValue('PicUrl');
	}

	/**
	 * 获取接收消息链接
	 * @return array url,title, description
	 */
	public function getUrl(){
		if (isset($this->data['Url'])){
			return array(
				'url'=>$this->data['Url'],
				'title'=>$this->data['Title'],
				'description'=>$this->data['Description']
			);
		}

		return null;
	}

	/**
	 * 获取接收地理位置
	 * @return array x, y, scale,label
	 */
	public function getGeo(){
		if (isset($this->data['Location_X'])){
			return array(
				'x'=>$this->data['Location_X'],
				'y'=>$this->data['Location_Y'],
				'scale'=>$this->data['Scale'],
				'label'=>$this->data['Label']
			);
		}

		return null;
	}

	public function getLocation() {
		if (isset($this->data['Latitude'])){
			return array(
				'latitude'=>$this->data['Latitude'],
				'longitude'=>$this->data['Longitude'],
				'precision'=>$this->data['Precision']
			);
		}

		return null;
	}

	/**
	 * 获取接收事件推送
	 * @return array event, key
	 */
	public function getEvent(){
		if (isset($this->data['Event'])){
			return array(
				'event'=>$this->data['Event'],
				'key'=>isset($this->data['EventKey'])? $this->data['EventKey']:null,
			);
		}

		return null;
	}

	/**
	 * 获取接收语言推送
	 * @return array mediaid, format
	 */
	public function getVoice(){
		if (isset($this->data['MediaId'])){
			return array(
				'mediaid'=>$this->data['MediaId'],
				'format'=>$this->data['Format'],
			);
		}

		return null;
	}

	/**
	 * 获取接收视频推送
	 * @return string meidaid, thumbmediaid
	 */
	public function getVideo(){
		if (isset($this->data['MediaId'])){
			return array(
				'mediaid'=>$this->data['MediaId'],
				'thumbmediaid'=>$this->data['ThumbMediaId']
			);
		}

		return null;
	}

	public function replyEchoStr() {
		echo $this->getEchoStr();
	}

	/**
	 * 回复文本消息
	 * @param string $text
	 */
	public function replyText($text) {
		$msg = array(
			'ToUserName' 	=>$this->getFromUserName(),
			'FromUserName'	=>$this->getToUserName(),
			'MsgType'		=>self::TYPE_TEXT,
			'Content'		=>$text,
			'CreateTime'	=>time(),
		);

		$this->send($msg);
	}

	/**
	 * 回复音乐消息
	 * @param string $title
	 * @param string $desc
	 * @param string $musicurl
	 * @param string $hgmusicurl
	 */
	public function replyMusic($title,$desc,$musicurl,$hgmusicurl='') {
		$msg = array(
			'ToUserName' => $this->getFromUserName(),
			'FromUserName'=>$this->getToUserName(),
			'CreateTime'=>time(),
			'MsgType'=>self::TYPE_MUSIC,
			'Music'=>array(
				'Title'=>$title,
				'Description'=>$desc,
				'MusicUrl'=>$musicurl,
				'HQMusicUrl'=>$hgmusicurl
			),
		);

		$this->send($msg);
	}

	/**
	 * 回复图文
	 * @param array $newsData
	 * 数组结构:
	 *  array(
	 *  	[0]=>array(
	 *  		'Title'=>'title',
	 *  		'Description'=>'description',
	 *  		'PicUrl'=>'http://www.test.com/test.jpg',
	 *  		'Url'=>'http://www.test.com/test.html'
	 *  	),
	 *  	[1]=>....
	 *  )
	 */
	public function replyNews($newsData=array()) {
		$msg = array(
			'ToUserName' => $this->getFromUserName(),
			'FromUserName'=>$this->getToUserName(),
			'MsgType'=>self::TYPE_NEWS,
			'CreateTime'=>time(),
			'ArticleCount'=>count($newsData),
			'Articles'=>$newsData,
		);

		$this->send($msg);
	}

	/**
	 * 转客服
	 * @param
	 */
	public function replyTransfer() {
		$msg = array(
			'ToUserName' 	=>$this->getFromUserName(),
			'FromUserName'	=>$this->getToUserName(),
			'MsgType'		=>self::TYPE_TRANSFER,
			'CreateTime'	=>time(),
		);

		$this->send($msg);
	}

	private function send($data) {
		$xml = $this->xmlEncode($data);
		$this->log("Send message:\r\n$xml\r\n");
		echo $xml;
	}

	private static function xmlEncode($data) {
		$xml   = "<xml>";
		$xml   .= self::data2Xml($data);
		$xml   .= "</xml>";
		return $xml;
	}

	/**
	 * 数据XML编码
	 * @param mixed $data 数据
	 * @return string
	 */
	private static function data2Xml($data) {
		$xml = '';
		foreach ($data as $key => $val) {
			is_numeric($key) && $key = "item id=\"$key\"";
			$xml    .=  "<$key>";
			$xml    .=  ( is_array($val) || is_object($val)) ? self::data2Xml($val)  : self::xmlSafeStr($val);
			list($key, ) = explode(' ', $key);
			$xml    .=  "</$key>";
		}

		return $xml;
	}

	/**
	 * xml文本字段CDATA包装
	 * @param string $str
	 * @return string
	 */
	private static function xmlSafeStr($str) {
		return '<![CDATA['.preg_replace("/[\\x00-\\x08\\x0b-\\x0c\\x0e-\\x1f]/",'',$str).']]>';
	}

	/**
	 * 验证消息真实性
	 * @return boolean 返回消息是否真实
	 */
	private function checkSignature($token) {
		$signature = isset($_GET['signature'])?$_GET['signature']:'';
		$timestamp = isset($_GET['timestamp'])?$_GET['timestamp']:'';
		$nonce = isset($_GET['nonce'])?$_GET['nonce']:'';

		$tmpArr = array($token, $timestamp, $nonce);
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );

		return ( $tmpStr == $signature );
	}

	private function getValue($key, $default=null) {
		return (isset($this->data[$key]))? $this->data[$key]:$default;
	}

	protected function log($data, $level='DEBUG') {
		if ($this->logger) {
			$date = date('Y-m-d H:i:s');
			$this->logger->log("[$date]\r\n".$data, $level);
		}
	}

	protected function setError($error) {
		$this->error = $error;
		$this->log($error, 'ERROR');
		return false;
	}

	/**
	 * @var array 消息数据
	 */
	private $data;
	/**
	 * @var IWechatLogger 日志记录器
	 */
	private $logger;
	/**
	 * @var string 错误信息
	 */
	private $error;
}

// End of file