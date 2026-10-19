<?php
/**
 * DataTableActiveForm.php
 *
 * @author suxh
 */

namespace app\widgets;

use app\widgets\DataTableView;
use yii\helpers\Html;

class DataTableActiveForm extends ActiveForm
{
	public $layout = 'inline';

	public $dataTableId = null;

	public function init(): void {
		$this->fieldConfig = array_merge(['template' => "{label}{input}{error}", 'labelOptions' => ['class' => 'form-label']], $this->fieldConfig);
		Html::addCssClass($this->options, 'data-table-search-form');
		if (!isset($this->options['onsubmit'])) {
			$this->options['onsubmit'] = 'return false;';
		}
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}

		if (!$this->dataTableId) {
			$this->dataTableId = $this->options['id'].'-data-table';
		}

		parent::init();
	}

	public function queryButton($options = []) {
		if (isset($options['value'])) {
			$value = $options['value'];
			unset($options['value']);
		} else {
			$value = '查询';
		}

		if (!isset($options['class'])) {
			$options['class'] = 'btn btn-primary';
		}

		if (!isset($options['onclick'])) {
			$options['onclick'] = "\$('#{$this->dataTableId}').data('dataTable').query();";
		}

		return Html::submitButton($value, $options);
	}

	public function table($config = []) {
		$config['id'] = $this->dataTableId;
		$config['post'] = '#'.$this->options['id'];

		return DataTableView::widget($config);
	}
}
