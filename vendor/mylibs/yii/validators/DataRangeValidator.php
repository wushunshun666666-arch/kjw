<?php
/**
 * DataRangeValidator.php
 *
 * @author James
 */

namespace mylibs\yii\validators;

use yii\validators\Validator;
use mylibs\DateTimeUtility;
class DataRangeValidator extends Validator
{
	/**
	 * 最早开始时间，如90-当前日期90天之前
	 * @var unknown
	 */
	public $minDate;

	/**
	 * 起始时间最大间隔
	 */
	public $period;

	public function validateAttribute($model, $attribute) {
		$time = $model->$attribute;

		$arr = explode('/', $time);
		if (count($arr) < 2) {
			$this->addError($model, $attribute, '时间周期格式错误。');
		}

		if ($this->period && DateTimeUtility::diffDays($arr[1], $arr[0]) > $this->period) {
			$this->addError($model, $attribute, '起始时间周期不能超过'.$this->period.'天');
		}

		if ($this->minDate && DateTimeUtility::diffDays('now', $arr[0]) > $this->minDate) {
			return $this->addError($model, $attribute, '开始时间不能超过当前日期'.$this->minDate.'天。');
		}
	}
}