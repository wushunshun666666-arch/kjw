<?php
/**
 * QQ号码验证器类
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\RegularExpressionValidator;

class QqValidator extends RegularExpressionValidator
{
	/**
	 * @var string 正则表达式
	 */
	public $pattern = '/^[1-9][0-9]*$/';
	public $message = 'QQ号码必须是大于0的整数。';
}