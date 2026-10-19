<?php
/**
 * 字母
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\RegularExpressionValidator;

class LetterValidator extends RegularExpressionValidator
{
	public $pattern = '/^[a-zA-Z]*$/u';
	public $message = '只能包含英文字母。';
}