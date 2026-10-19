<?php
/**
 * 库位编号验证器
 * @author suxh
 */

namespace common\validators;

use yii\validators\RegularExpressionValidator;

class LocationNumberValidator extends RegularExpressionValidator
{
	public $multi = false;

	/**
	 * @inheritdoc
	 */
	public function init() {
		if ($this->multi) {
			//$this->pattern = '/^[!-~]+([\n\r]+[!-~]*)*$/';
			$this->pattern = '/^[!-~]{1,30}([\n\r]+[!-~]{0,30}){0,499}$/';
			$this->message = '只能包含数字、英文字母和符号并且最多30个字符，最多输入500行。';
		}
		else {
			$this->pattern = '/^[!-~]{1,30}$/';
			$this->message = '只能包含数字、英文字母和符号并且最多30个字符。';
		}
	}
}