<?php
/**
 * Url.php
 *
 * @author suxh
 */

namespace app\helpers;

class Url extends \yii\helpers\Url
{
	public static function toJs($url = '', $scheme = false) {
		$route = $url[0];
		$url = self::to($url, $scheme);

		return Json::htmlEncode([$route, $url]);
	}

	/**
	 * 构造资源文件的URL地址
	 * @param string $file 文件名和相对路径，如：/template/xxx.csv
	 * @return string 返回资源文件的URL地址
	 */
	public static function toResource($file) {
		return self::to(['resource'.$file], true);
	}
}