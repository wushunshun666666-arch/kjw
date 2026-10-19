<?php
/**
 * IWechatLogger.php
 * @author suxh
 * @package
 */

namespace mylibs\wechat;
interface IWechatLogger
{
	public function log($message, $level='DEBUG');
}

// End of file