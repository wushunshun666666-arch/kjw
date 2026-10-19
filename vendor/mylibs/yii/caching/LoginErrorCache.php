<?php
/** 
 * 登录错误缓存
 * 统计和记录IP登录错误次数
 * @author suxh
 */

namespace mylibs\yii\caching;

class LoginErrorCache extends AppCache
{
	public static function add() {
		return self::getInstance()->addError();
	}
	
	public static function clear() {
		return self::getInstance()->clearError();
	}
	
	public static function overLimit($limit = 3) {
		return self::getInstance()->isOverLimit($limit);
	}
	
	public function __construct() {
		parent::__construct(\Yii::$app->id);
	}
	
	public function addError() {
		$key = $this->getUserIP();
		$this->num = intval($this->cache->get($key)) + 1;
		$this->cache->set($key, $this->num, $this->expire);
		
		return $this->num;
	}
	
	public function clearError() {
		$key = $this->getUserIP();
		$this->cache->delete($key);
		$this->num = null;
	}
	
	public function isOverLimit($limit = 3) {
		if ($this->num == null) {
			$key = $this->getUserIP();
			$this->num = $this->cache->get($key);
		}
		
		return ($this->num >= 3);
	}
	
	private function getUserIP() {
		return \Yii::$app->request->getUserIP();
	}
	
	private static function getInstance() {
		if (self::$instance == null) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	private static $instance = null;
	
	private $expire = 3600;
	private $num = null;
}