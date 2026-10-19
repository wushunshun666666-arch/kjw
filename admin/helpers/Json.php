<?php
/**
 * Json.php
 *
 * @author suxh
 */

namespace app\helpers;

class Json extends \yii\helpers\Json
{
	public static function encode($value, $options = 320) {
		$expressions = [];
		$value = static::processData($value, $expressions, rand(1000000, 9999999));
		$json = json_encode($value, $options);
	
		return empty($expressions) ? $json : strtr($json, $expressions);
	}
}