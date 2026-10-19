<?php
/**
 * ISms.php
 *
 * @author James
 */

namespace mylibs\sms\base;

interface ISms
{
	/**
	 * 发送短信
	 * @param mixed $mobile 手机号码，多个时传数组
	 * @param string $message
	 * @param mixed $options
	 */
	public function send($mobile, $message, $options=null);
}