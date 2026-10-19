<?php
/**
 * 汽车排量验证器
 * @author suxh
 */

namespace common\validators;

use yii\validators\RegularExpressionValidator;

class DisplacementValidator extends RegularExpressionValidator
{
	public $pattern = '/^[0-9]+\.[0-9]+[LT]$/';
	public $message = '排量格式错误，正确格式如：1.0L/1.0T';
}