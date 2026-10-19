<?php
/**
 * WechatBuilder.php
 *
 * @author James
 */

namespace mylibs\yii\wechat;

use mylibs\yii\config\Configure;
use mylibs\wechat\WechatConfig;
use mylibs\wechat\Wechat;
class WechatBuilder
{
	public function __construct() {
		$configure = new Configure();
		$config = $configure->loadCommonConfig('wechat');

		$config = new WechatConfig($config);
		$this->wechat = new Wechat($config, new Logger());
	}

	public function getWechat() {
		return $this->wechat;
	}

	private $wechat;
}