<?php
/**
 * 格式化
 *
 * @author suxh
 */

namespace mylibs\yii\helpers;

class Formatter
{
	public static function asCurrency($value) {
		return \Yii::$app->formatter->asDecimal($value, 2);
	}

	public static function asDate($value) {
		return date('Y年m月d日', strtotime($value));
	}
}