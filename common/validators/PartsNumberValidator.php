<?php
/**
 * 配件编号验证器
 * @author suxh
 */

namespace common\validators;

use yii\validators\RegularExpressionValidator;

class PartsNumberValidator extends RegularExpressionValidator
{
	public $multi = false;

	/**
	 * @inheritdoc
	 */
	public function init() {
		if ($this->multi) {
			$this->pattern = '/^[!-~^,…]+(,[!-~^,…]+)*$/';
			$this->message = '只能包含数字、英文字母和符号，多个编码之间使用“,”分隔。';
		}
		else {
			$this->pattern = '/^[!-~^,…]*$/';
			$this->message = '只能包含数字、英文字母和符号。';
		}
	}
}