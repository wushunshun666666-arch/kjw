<?php
/**
 * 随机字符串生成工具
 * @author suxh
 */

namespace mylibs;

class RandString
{
	/**
	 * 产生随机数字字符串
	 * @param integer $length 字串长度
	 * @return string 返回生成的字串
	 */
	public static function integer($length = 4) {
		$chars = '0123456789';
		$code = '';
		for($i = 0; $i < $length; $i++) {
			$code .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}

		return $code;
	}

	/**
	 * 产生随机简单字母数字字符串(只由大小写字母和数字组成，不包含特殊字符)
	 * @param integer $length 字串长度
	 * @return string 返回生成的字串
	 */
	public static function simple($length) {
		$chars = 'ABCDEEGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		$code = '';
		for($i = 0; $i < $length; $i++) {
			$code .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
		}

		return $code;
	}

	/**
	 * 产生随机复杂字符串(同时包含大小写字母、数字和特殊字符)
	 * @param integer $length 字串长度
	 * @return string 返回生成的字串
	 */
	public static function complex($length) {
		if ($length < 4) {
			throw new \Exception('长度不能小于4');
		}

		$num = $length/4;
		$lowerCharNum = $num + $length%4;

		$words = array();

		$digit = range(0, 9);
		shuffle($digit);
		$words = array_merge($words, array_slice($digit, 0, $num));

		$upperChar = range('A', 'Z');
		shuffle($upperChar);
		$words = array_merge($words, array_slice($upperChar, 0, $num));

		$lowerChar = range('a', 'z');
		shuffle($lowerChar);
		$words = array_merge($words, array_slice($lowerChar, 0, $lowerCharNum));

		$specialChar = array('~','!','@','#','$','%','^','&','*','(',')','-','+',';',',','.','?');
		shuffle($specialChar);
		$words = array_merge($words, array_slice($specialChar, 0, $num));

		shuffle($words);
		return implode('', $words);
	}
}