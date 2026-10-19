<?php
/**
 * 名称验证器类
 * 名称，由中文、大小写英文字母、数字、下划线、中划线、小括号、空格组成。
 * 适用于类别名称、类型名称等
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\RegularExpressionValidator;

class NameValidator extends RegularExpressionValidator
{
	public $pattern = '/^[\x{4e00}-\x{9fa5}\x{f900}-\x{fa2d}a-zA-Z0-9\(\)（） _-]*$/u';
	public $message = '只能包含中文、大小写英文字母、数字、下划线、中划线、小括号、空格。';
}