<?php
/**
 * Logger.php
 *
 * @author James
 */

namespace mylibs\yii\wechat;

use mylibs\wechat\IWechatLogger;
class Logger implements IWechatLogger
{
	public function log($message, $level='DEBUG') {
		\Yii::info($message, 'wechat');
	}
}