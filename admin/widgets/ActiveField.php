<?php
/**
 * ActiveField.php
 *
 * @author suxh
 */

namespace app\widgets;

use app\helpers\Html;
use common\widgets\RegionWidget;
use common\widgets\ResourceFileUploadWidget;
use mylibs\yii\widgets\datePicker\DatePicker;
use mylibs\yii\widgets\datePicker\DateRangePicker;
use mylibs\yii\widgets\combobox\ComboBox;
use mylibs\yii\widgets\ckeditor\CkEditor;
use mylibs\yii\widgets\viewmap\SetMap;
use yii\web\JsExpression;

class ActiveField extends \yii\bootstrap5\ActiveField
{
	private $requiredCssClass = null;

	public function init(): void {
		parent::init();

		// The project submits most forms through a custom AJAX button. Keep the
		// browser constraint API in sync with Yii rules so validation can never be
		// bypassed if the Yii ActiveForm instance has not initialized yet.
		if ($this->model->isAttributeRequired($this->attribute)) {
			$this->inputOptions['required'] = true;
		}
	}

	public function readonly($options = []) {
		$this->enableClientValidation = false;
		$this->enableError = false;
		$this->configureReadonlyLayout();
		$options['readonly'] = 'readonly';
		// TODO
		$this->requiredCssClass = $this->form->requiredCssClass;
		$this->form->requiredCssClass = '';

		if (isset($options['class']) && isset($this->inputOptions['class'])) {
			$options['class'] = $this->inputOptions['class'].' '.$options['class'];
		}

		return parent::textInput($options);
	}

	public function readonlyTextarea($options = []) {
		$this->enableClientValidation = false;
		$this->enableError = false;
		$this->configureReadonlyLayout();
		$options['readonly'] = 'readonly';
		// TODO
		$this->requiredCssClass = $this->form->requiredCssClass;
		$this->form->requiredCssClass = '';
		return parent::textarea($options);
	}

	/**
	 * 只读详情字段统一使用 Bootstrap 5 横向表单的旧版展示比例。
	 */
	private function configureReadonlyLayout(): void {
		if ($this->form->layout !== 'horizontal') {
			return;
		}

		$this->labelOptions['class'] = 'col-sm-2 col-form-label text-sm-end fw-semibold pe-sm-3';
		$this->wrapperOptions['class'] = 'col-sm-6';
		$this->hintOptions['class'] = 'col-sm-2 form-text text-muted';
	}

	public function end() {
		if ($this->requiredCssClass) {
			$this->form->requiredCssClass = $this->requiredCssClass;
		}
		return parent::end();
	}

	public function queryInput($options=[]) {
		$options = array_merge($this->inputOptions, $options);
		$this->adjustLabelFor($options);

		$this->parts['{input}'] = Html::activeQueryInput($this->model, $this->attribute, $options);

		return $this;
	}

	public function datePicker($options = []) {
		$model = $this->model;
		$attribute = $this->attribute;
		$name = isset($options['name']) ? $options['name'] : Html::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : Html::getAttributeValue($model, $attribute);
		$id = isset($options['id'])? $options['id'] : Html::getInputId($model, $attribute);

		$clientOptions = [];
		if (isset($options['clientOptions'])) {
			$clientOptions = $options['clientOptions'];
			unset($options['clientOptions']);
		}
		if (isset($options['minDate'])) {
			$clientOptions['minDate'] = $options['minDate'];
			unset($options['minDate']);
		}
		if (isset($options['maxDate'])) {
			$clientOptions['maxDate'] = $options['maxDate'];
			unset($options['maxDate']);
		}

		$options = array_merge($this->inputOptions, $options);
		$options['class'] .= ' date-picker';

		$this->parts['{input}'] = DatePicker::widget([
			'id'		=> $id,
			'name'		=> $name,
			'value' 	=> $value,
			'options' 	=> $options,
			'clientOptions' => $clientOptions,
		]);

		return $this;
	}

	public function dateRangePicker($options = []) {
		$model = $this->model;
		$attribute = $this->attribute;
		$name = isset($options['name']) ? $options['name'] : Html::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : Html::getAttributeValue($model, $attribute);
		$id = isset($options['id'])? $options['id'] : Html::getInputId($model, $attribute);
		$minDate = isset($options['minDate']) ? $options['minDate'] : null;
		$maxDate = isset($options['maxDate']) ? $options['maxDate'] : '%y-%M-%d';

		$inputOptions = array_merge($this->inputOptions, $options);
		unset($inputOptions['minDate'], $inputOptions['maxDate'], $inputOptions['name'], $inputOptions['value'], $inputOptions['id']);
		$inputOptions['class'] .= ' date-picker';
		$hiddenOptions = $options;
		unset($hiddenOptions['minDate'], $hiddenOptions['maxDate'], $hiddenOptions['name'], $hiddenOptions['value']);
		$this->parts['{input}'] = DateRangePicker::widget([
			'id'		=> $id,
			'name'		=> $name,
			'value' 	=> $value,
			'minDate'	=> $minDate,
			'maxDate'	=> $maxDate,
			'options' 	=> $hiddenOptions,
			'inline'	=> $this->inline,
			'inputOptions' => $inputOptions,
		]);

		return $this;
	}

	public function setMap($options=[]){
		$model = $this->model;
		$attribute = $this->attribute;

		$name = isset($options['name']) ? $options['name'] : Html::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : Html::getAttributeValue($model, $attribute);
		$region = isset($options['region']) ? $options['region'] : '';
		$mapHeight = isset($options['mapHeight']) ? $options['mapHeight'] : '';
		return SetMap::widget([
			'name'		=> $name,
			'value'		=> $value,
			'region' 	=> $region,
			'mapHeight'=> $mapHeight,
			'options'	=> $options,
		]);
	}

	public function editor($options = []) {
		$model = $this->model;
		$attribute = $this->attribute;
		$name = isset($options['name']) ? $options['name'] : Html::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : Html::getAttributeValue($model, $attribute);
		$id = isset($options['id'])? $options['id'] : Html::getInputId($model, $attribute);

		$onInstanceReady = new JsExpression("function(ev) {
			ev.editor.on('blur', function(e) {
				ev.editor.updateElement();
				jQuery('#$id').trigger('blur');
			});
		}");
		$clientOptions = [];
		$clientOptions['on'] = [
			'instanceReady' => $onInstanceReady,
		];
		$this->parts['{input}'] = CkEditor::widget([
			'id'		=> $id,
			'name'		=> $name,
			'value' 	=> $value,
			'options' 	=> $options,
			'clientOptions' => $clientOptions,
		]);

		return $this;
	}

	public function combobox($items, $options = []) {
		$model = $this->model;
		$attribute = $this->attribute;
		$name = isset($options['name']) ? $options['name'] : Html::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : Html::getAttributeValue($model, $attribute);
		$id = isset($options['id'])? $options['id'] : Html::getInputId($model, $attribute);

		$placeholder = '';
		if (isset($options['placeholder'])) {
			$placeholder = $options['placeholder'];
			unset($options['placeholder']);
		}
		// ComboBox 的原始控件是 select，Bootstrap 5 应使用 form-select，
		// Selectize 初始化后再由 bootstrap5-combobox 类统一生成层的样式。
		Html::removeCssClass($options, 'form-control');
		Html::addCssClass($options, ['combobox', 'form-select']);
		$options['aria-label'] = $options['aria-label'] ?? ($placeholder ?: $this->model->getAttributeLabel($attribute));
		$emptyLabel = $placeholder ?: '请选择';
		$this->parts['{input}'] = ComboBox::widget([
			'id'		=> $id,
			'name'		=> $name,
			'selection' => $value,
			'items'		=> ['' => $emptyLabel] + $items,
			'placeholder' => $placeholder,
			'options' 	=> $options,
		]);

		return $this;
	}

	public function resourceFile($options = []) {
		$id = isset($options['id'])? $options['id'] : Html::getInputId($this->model, $this->attribute);
		$imgId = $id.'-img';

		$imgOptions = [];
		$imgOptions['id'] = $imgId;
		$imgOptions['class'] = 'img-show';
		if (isset($options['width'])) {
			$imgOptions['width'] = $options['width'];
		}
		if (isset($options['height'])) {
			$imgOptions['height'] = $options['height'];
		}

		$value = Html::getAttributeValue($this->model, $this->attribute);

		$input = Html::activeHiddenInput($this->model, $this->attribute);
		if ($value) {
			$input .= Html::resourceImg($value, $imgOptions);
		}
		else {
			$imgOptions['style'] = 'display:none;';
			$input .= Html::img('', $imgOptions);
		}

		$input .= ResourceFileUploadWidget::widget([
			'clientOptions'=>['success'=>new JsExpression("function(data) {\$('#{$imgId}').attr('src', data.url).show();\$('#{$id}').val(data.alias).trigger('blur');}")],
			'buttonContent'=>'上传图片',
			'resource' => $options['resource'],
		]);
		if (isset($options['tip'])) {
			$input .= '<div class="tip"><i class="fa fa-bell-o"></i>'.$options['tip'].'</div>';
		}

		$this->parts['{input}'] = $input;

		return $this;
	}

	public function searchDropDownList($items, $options = []) {
		if (!isset($options['emptyValue'])) $options['emptyValue'] = '';
		if (!isset($options['emptyName'])) $options['emptyName'] = '全部';
		$items = array($options['emptyValue']=>$options['emptyName']) + $items;

		return $this->dropDownList($items, $options);
	}

	public function selectDropDownList($items, $options = []) {
		if (!isset($options['emptyValue'])) $options['emptyValue'] = '';
		if (!isset($options['emptyName'])) $options['emptyName'] = '请选择';
		$items = array($options['emptyValue']=>$options['emptyName']) + $items;

		return $this->dropDownList($items, $options);
	}

	public function dropDownList($items, $options = []): self {
		$value = (isset($options['emptyValue'])) ? $options['emptyValue'] : '';
		$name = (isset($options['emptyName'])) ? $options['emptyName'] : '全部';

		$options = Html::setDropDownListAjaxOptions($options, $value, $name);
		return parent::dropDownList($items, $options);
	}

	public function searchRegionDropDownList($options = []) {
		if (!isset($options['emptyName'])) {
			$options['emptyName'] = '全部';
		}
		return $this->regionDropDownList('any', $options);
	}

	public function selectRegionDropDownList($options = []) {
		if (!isset($options['emptyName'])) {
			$options['emptyName'] = '请选择';
		}
		return $this->regionDropDownList('district', $options);
	}

	protected function regionDropDownList($selectLevel, $options = []) {
		$model = $this->model;
		$attribute = $this->attribute;
		$name = isset($options['name']) ? $options['name'] : Html::getInputName($model, $attribute);
		$value = isset($options['value']) ? $options['value'] : Html::getAttributeValue($model, $attribute);
		$id = isset($options['id'])? $options['id'] : Html::getInputId($model, $attribute);

		if (isset($options['emptyName'])) {
			$emptyName = $options['emptyName'];
			unset($options['emptyName']);
		}
		else {
			$emptyName = null;
		}

		$selectOptions = array_merge($this->inputOptions, $options);
		unset($selectOptions['name'], $selectOptions['value'], $selectOptions['id']);

		$this->parts['{input}'] = RegionWidget::widget([
			'inputId'		  => $id,
			'inputName'		  => $name,
			'inputValue' 	  => $value,
			'selectLevel'	  => $selectLevel,
			'emptyName' 	  => $emptyName,
			'provinceOptions' => $selectOptions,
			'cityOptions'	  => $selectOptions,
			'districtOptions' => $selectOptions,
		]);

		return $this;
	}
}
