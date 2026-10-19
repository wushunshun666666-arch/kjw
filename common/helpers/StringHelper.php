<?php
/**
 * StringHelper.php
 * @author James
 */

namespace common\helpers;

use yii\helpers\BaseStringHelper;
class StringHelper extends BaseStringHelper
{
	/**
	 * 是否M码
	 * @param string $number
	 */
	public static function isProductNumber($number) {
		$pattern = '/^M\d{11}$/';
		return preg_match($pattern, $number);
	}

	/**
	 * 格式化字符串-省略
	 * @param  string  $str
	 * @param  integer $tailLength   尾部保留长度
	 * @param  integer $headerLength 头部保留长度
	 * @param  string  $replace      替换字符
	 * @return string
	 *
	 * eg: ellipsisString('123456789', 3, 0, '***') => '***789'
	 */
	public static function ellipsisString($str, $tailLength, $headerLength=0, $replace='***') {
		$endLength = strlen($str)-$tailLength-$headerLength;
		if ($endLength < 0) {
			return $str;
		}

		return str_replace(substr($str, $headerLength, $endLength), $replace, $str);
	}
}