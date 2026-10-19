<?php
/** 
 * 公司名称/组织名称/单位名称
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\RegularExpressionValidator;

class CompanyNameValidator extends RegularExpressionValidator
{
	public $pattern = '/^[\x{4e00}-\x{9fa5}\x{f900}-\x{fa2d}（）a-zA-Z0-9().,& _-]*$/u';
	public $message = '只能包含中文、数字、英文字母、空格、括号及.,&符号。';
}
