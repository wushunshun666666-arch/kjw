<?php
/** 
 * 多个IP验证器类
 * 多个IP使用空格、换行符分割。
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\RegularExpressionValidator;

class MultiIpValidator extends RegularExpressionValidator
{
	/**
	 * @var integer 至少匹配$min个IP
	 */
	public $min;
	/**
	 * @var integer 最多匹配$max个IP
	 */
	public $max;

	/**
	 * @inheritdoc
	 */
	public function init() {
		$min = $this->min;
		$max = $this->max;
		if($min > 0 && $max > 0) {
			$message = "必须符合IP地址格式，至少{$min}个IP地址，最多{$max}个IP地址。";
			$min -= 1;$max -= 1;
			$numPattern = '{'.$min. ',' .$max.'}';
		}
		elseif($min > 0) {
			$message = "必须符合IP地址格式，至少{$min}个IP地址。";
			$min -= 1;
			$numPattern = '{'.$min. ',}';
		}
		elseif($max > 0) {
			$message = "必须符合IP地址格式，最多{$max}个IP地址。";
			$max -= 1;
			$numPattern = '{0,' .$max.'}';
		} else {
			$message = '必须符合IP地址格式。';
			$numPattern = '*';
		}

		$ipPatten = '(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])';
		$this->pattern = '/^[\n\r\t ]*'.$ipPatten.'+([\n\r\t ]+'.$ipPatten.')'.$numPattern.'[\n\r\t ]*$/';
		$this->message = (empty($this->message))? $message:$this->message;
	}
}

