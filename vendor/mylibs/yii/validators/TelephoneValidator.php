<?php
/**
 * 电话号码验证器类
 * 固定电话：3-4位区号，7-8位直拨号码，1-4位分机号。
 * 移动电话：11位号码。
 *
 * @author suxh
 */

namespace mylibs\yii\validators;

use yii\validators\Validator;
use yii\helpers\Json;

class TelephoneValidator extends Validator
{
	/**
	 * @var string 电话号码类型， 默认为固定电话。可选择的值包括：'telephone','mobi','all'
	 */
	public $type = 'telephone';

	/**
	 * @var string 固定电话号码格式正则表达式
	 */
	public $pattern = '/^(\d{1,4}-)?\d{7,8}(-\d{1,4})?$/';

	/**
	 * @var string 移动电话号码格式正则表达式
	 */
	public $patternMobi = '/^\d{11}$/';

	/**
	 * @var boolean 是否允许为空，默认为true
	 */
	public $allowEmpty = true;

	/**
	 * @inheritdoc
	 */
	public function init() {
		if (empty($this->message)) {
			if ($this->type == 'mobi') {
				$this->message = '无效的手机号码。';
			}
			else {
				$this->message = '无效的电话号码。';
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($model, $attribute) {
		$value = $model->$attribute;
		if ($this->allowEmpty && trim($value) == '') {
			return;
		}

		if(($this->type == 'telephone' && !preg_match($this->pattern, $value)) ||
			($this->type == 'mobi' && !preg_match($this->patternMobi, $value)) ||
			(!(preg_match($this->pattern, $value) || preg_match($this->patternMobi, $value)))) {
			$this->addError($model,$attribute,$this->message);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($model, $attribute, $view) {
		$message=$this->message;

		switch ($this->type) {
			case 'telephone':
				$condition="!value.match({$this->pattern})";break;
			case 'mobi':
				$condition="!value.match({$this->patternMobi})";break;
			default:
				$condition="!(value.match({$this->pattern})||value.match({$this->patternMobi}))";break;
		}

		if ($this->allowEmpty) {
			$condition = "$.trim(value)!='' && ".$condition;
		}


		return "if({$condition}) {messages.push(".Json::encode($message).");}";
	}
}