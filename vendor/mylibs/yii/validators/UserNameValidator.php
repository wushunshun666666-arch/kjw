<?php
/**
 * 用户名验证器类
 * 由大小写英文字母、数字、中划线、下划线、组成，并且必须以英文字母开头。
 *
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\RegularExpressionValidator;

class UserNameValidator extends RegularExpressionValidator
{
	/**
	 * @var string 用户名格式正则表达式
	 */
	public $pattern = '/^[a-zA-Z][a-zA-Z0-9_-]*$/';
	public $message = '只能包含英文字母、数字、中划线、下划线，并且以英文字母开头。';
}