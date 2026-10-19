<?php
/**
 * DatePicker.php
 * @author suxh
 */

namespace mylibs\yii\widgets\datePicker;

use yii\base\Widget;
use yii\helpers\Html;

class DatePicker extends Widget
{
	public $name;
	public $value;
	public $options = ['class'=>'date-picker'];
	public $clientOptions = [];

	public function init() {
		parent::init();
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}
	}

	public function run() {
		$options = $this->options;
		Html::addCssClass($options, ['form-control', 'date-picker']);
		$options['data-native-date-picker'] = '1';

		if (!isset($options['min']) && !empty($this->clientOptions['minDate'])) {
			$options['min'] = $this->normalizeDate($this->clientOptions['minDate']);
		}
		if (!isset($options['max']) && !empty($this->clientOptions['maxDate'])) {
			$options['max'] = $this->normalizeDate($this->clientOptions['maxDate']);
		}
		if (isset($options['min'])) {
			$options['min'] = $this->normalizeDate($options['min']);
		}
		if (isset($options['max'])) {
			$options['max'] = $this->normalizeDate($options['max']);
		}

		echo Html::input('date', $this->name, $this->normalizeDate($this->value), $options);

		$view = $this->getView();
		DatePickerAsset::register($view);
	}

	private function normalizeDate($value): string {
		$value = trim((string)($value ?? ''));
		if ($value === '') {
			return '';
		}
		if ($value === '%y-%M-%d') {
			return date('Y-m-d');
		}
		if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $matches)) {
			return $matches[0];
		}

		$timestamp = strtotime($value);
		return $timestamp === false ? '' : date('Y-m-d', $timestamp);
	}
}
