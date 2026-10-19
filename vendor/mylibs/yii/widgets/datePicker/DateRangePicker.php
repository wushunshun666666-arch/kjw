<?php
/**
 * DateRangePicker.php
 * @author suxh
 */

namespace mylibs\yii\widgets\datePicker;

use yii\base\Widget;
use yii\helpers\Html;

class DateRangePicker extends Widget
{
	public $name;
	public $value;
	public $inline = false;
	public $maxDate = '%y-%M-%d';
	public $minDate = null;
	public $options = [];
	public $inputOptions = [];
	public $wrapTag = 'span';
	public $wrapOptions = ['class'=>'date-range-picker'];

	public function init() {
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}
	}

	public function run() {
		$values 	= explode('/', (string)($this->value ?? ''));
		$startValue = (isset($values[0]))? $values[0] : '';
		$endValue 	= (isset($values[1]))? $values[1] : '';
		$id 		= $this->options['id'];
		$startId 	= $id.'-start';
		$endId 		= $id.'-end';

		$inputOptions = $this->inputOptions;
		Html::addCssClass($inputOptions, 'date-picker');
		$startOptions = $inputOptions;
		$endOptions = $inputOptions;
		$startOptions['data-date-range-part'] = 'start';
		$endOptions['data-date-range-part'] = 'end';
		if (!empty($this->minDate)) {
			$startOptions['min'] = $this->minDate;
			$endOptions['min'] = $startValue ?: $this->minDate;
		}
		elseif ($startValue) {
			$endOptions['min'] = $startValue;
		}
		if (!empty($this->maxDate)) {
			$startOptions['max'] = $endValue ?: $this->maxDate;
			$endOptions['max'] = $this->maxDate;
		}
		elseif ($endValue) {
			$startOptions['max'] = $endValue;
		}

		if ($this->inline) {
			Html::addCssClass($this->wrapOptions, 'd-inline-flex align-items-center gap-1');
		}

		$wrapOptions = $this->wrapOptions;
		Html::addCssClass($wrapOptions, 'date-range-picker');
		$wrapOptions['data-date-range-picker'] = '1';
		$wrapOptions['data-target'] = $id;
		$wrapOptions['data-min-date'] = (string)($this->minDate ?? '');
		$wrapOptions['data-max-date'] = (string)($this->maxDate ?? '');
		echo Html::beginTag($this->wrapTag, $wrapOptions);
		echo Html::hiddenInput($this->name, $this->value, $this->options);
		echo DatePicker::widget([
			'id'			=> $startId,
			'value' 		=> $startValue,
			'options' 		=> $startOptions,
		]);
		echo Html::tag('span', '~', ['class' => 'date-range-separator', 'aria-hidden' => 'true']);
		echo DatePicker::widget([
			'id'			=> $endId,
			'value' 		=> $endValue,
			'options' 		=> $endOptions,
		]);
		echo Html::endTag($this->wrapTag);
	}
}
