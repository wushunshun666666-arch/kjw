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

class MultiTelephoneValidator extends Validator
{
	/**
	 * @var string 电话号码类型， 默认为固定电话。可选择的值包括：'telephone','mobi','all'
	 */
	public $type = 'telephone';
	/**
	 * @var integer
	 */
	public $min;
	/**
	 * @var integer
	 */
	public $max;

	/**
	 * @var boolean 是否允许为空，默认为true
	 */
	public $allowEmpty = true;

	/**
	 * @var string 电话号码格式正则表达式
	 */
	public $pattern;
	public $minMessage;
	public $maxMessage;

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
		$patterns = [
			'mobi' 		=> '/^\d{11}$/',
			'telephone' => '/^(\d{1,4}-)?\d{7,8}(-\d{1,4})?$/',
			'all' 		=> '/^(\d{1,4}-)?\d{7,8}(-\d{1,4})?$|^\d{11}$/',
		];
		$this->pattern = $patterns[$this->type];

		$this->minMessage = (!empty($this->minMessage)) ?: "必须至少输入{$this->min}个号码";
		$this->maxMessage = (!empty($this->maxMessage)) ?: "最多只能输入{$this->max}个号码";
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($model, $attribute) {
		$value = $model->$attribute;
		if ($this->allowEmpty && trim($value) == '') {
			return;
		}

		$tels = array_diff(preg_split('/[\n\r\t]+/', $value), ['', null]);
		if ($this->min && count($tels) < $this->min) {
			return $this->addError($model, $attribute, $this->minMessage);
		}
		if ($this->max && count($tels) > $this->max) {
			return $this->addError($model, $attribute, $this->maxMessage);
		}
		foreach ($tels as $tel) {
			if(!preg_match($this->pattern, trim($tel))) {
				$this->addError($model, $attribute, $tel.$this->message);
				break;
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($model, $attribute, $view) {
		$message = Json::encode($this->message);

		$js = '';
		if ($this->allowEmpty) {
			$js = "if ($.trim(value)=='') {return;}";
		}
		$js.='var strs = value.split("\n");var tels=[];for(i in strs) {if ($.trim(strs[i]) != "") {tels.push(strs[i]);}}';
		if ($this->min) {
			$js.="if (tels.length < {$this->min}) {messages.push(".Json::encode($this->minMessage).");}";
		}
		if ($this->max) {
			$js.="if (tels.length > {$this->max}) {messages.push(".Json::encode($this->maxMessage).");}";
		}
		$js.=<<<JS
	for (i in tels) {
		if (!$.trim(tels[i]).match({$this->pattern})) {
			messages.push(tels[i]+{$message});
			break;
		}
	}
JS;

		return $js;
	}
}