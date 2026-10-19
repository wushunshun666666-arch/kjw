<?php
/**
 * SnCoder.php
 * @author suxh
 */

namespace mylibs\coder;

class SnCoder
{
	public static function getMaxSerial($length) {
		return pow(count(self::$codes), $length) - 1;
	}

	/**
	 * 将数字系列转为SN系列号，返回$length位
	 * @param integer $serial
	 */
	public static function serialEncode($serial,$length=4) {
        $code='';
        while ($serial >= 34)
        {
            $code = self::$codes[$serial % 34].$code;
            $serial /= 34;
        }
        if ($serial >= 0) $code = self::$codes[$serial].$code;
        return str_pad($code, $length,'0',STR_PAD_LEFT);
	}

	/**
	 * 将日期转为SN系列号
	 * @param string $date 日期
	 */
	public static function dateEncode($date) {
	    $arr = explode('/', (string)($date ?? ''));
	    if(count($arr)<3){
	        return '';
	    }
	    $year  = $arr[0];
	    $month = $arr[1];
	    $day   = $arr[2];

	    $m_int = (int)$month;
	    if($m_int > 33){
	        return '';
	    }
	    $d_int = (int)$day;
	    if($d_int > 33){
	        return '';
	    }
        return $year.self::$codes[$m_int].self::$codes[$d_int];
	}

	/**
	 * 将$string 34进制转换为10进制
	 * @param string $string
	 */
	public static function decode($string) {
	    $num = 0;
	    $len = strlen($string);
	    for ($i=$len;$i>0;$i--){
	        $key = array_search($string[$i-1],self::$codes);
	        $num += $key*(pow(34, $len-$i));
	    }
	    return $num;
	}

	/**
	 * 获取$bit位随机数
	 * @param string $string
	 */
	public static function getRandCode($bit=4){
	    $rand='';
	    for($i=0; $i<$bit; $i++){
	        $rand = $rand.self::$codes[mt_rand(0, count(self::$codes)-1)];
	    }
	    return $rand;
	}

	private static $codes = [
		'0','1','2','3','4','5','6','7','8','9',
		'A','B','C','D','E','F','G','H','J','K',
		'L','M','N','P','Q','R','S','T','U','V',
		'W','X','Y','Z'
	];
}
