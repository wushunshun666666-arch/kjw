<?php
/**
 * 数值Decimal格式验证器类
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\RegularExpressionValidator;

class DecimalValidator extends RegularExpressionValidator
{
	public $maximum = null;
	public $decimalnum = 2;

	/**
	 * @inheritdoc
	 */
	public function init() {
		if ($this->maximum == null) {
			$this->pattern = '/^(0|[1-9]\d*)(\.\d{0,'.$this->decimalnum.'})?$/';
			if ($this->message === null) {
				$this->message = "必须是数字，并且小数部分最多{$this->decimalnum}位。";
			}
		}
		else {
			$digitnum = $this->maximum - $this->decimalnum;
			$num = $digitnum -1;
			$this->pattern = '/^(0|[1-9]\d{0,'.$num.'})(\.\d{0,'.$this->decimalnum.'})?$/';
			if ($this->message === null) {
				$this->message = "必须是数字，并且整数部分最多{$digitnum}位，小数部分最多{$this->decimalnum}位。";
			}
		}
	}
}