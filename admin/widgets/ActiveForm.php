<?php
/**
 * ActiveForm.php
 *
 * @author suxh
 */

namespace app\widgets;
use app\helpers\Html;

class ActiveForm extends \yii\bootstrap5\ActiveForm
{
	public $fieldClass = 'app\widgets\ActiveField';

	public function init(): void {
		// 水平双列布局
		if ($this->layout == 'horizontal-tow-col') {
			if (!isset($this->fieldConfig['horizontalCssClasses'])) {
				$this->fieldConfig['horizontalCssClasses'] = [
					'wrapper' => 'col-sm-8',
					'label' => 'col-sm-2 col-form-label text-sm-end fw-semibold pe-sm-3',
					'hint' => 'col-sm-2',
				];
			}
			$this->layout = 'horizontal';
		}
		// 水平单列布局
		else if ($this->layout == 'horizontal') {
			if (!isset($this->fieldConfig['horizontalCssClasses'])) {
				$this->fieldConfig['horizontalCssClasses'] = [
					'wrapper' => 'col-sm-6',
					'label' => 'col-sm-2 col-form-label text-sm-end fw-semibold pe-sm-3',
					'hint' => 'col-sm-3',
				];
			}
		}

		// Bootstrap 5 默认把 hint 放在输入框 wrapper 内，会导致 attributeHints
		// 换到输入框下方。项目的横向表单为 label/input/hint 三列布局，
		// 因此将 hint 恢复为 wrapper 后的同级元素。
		if ($this->layout === 'horizontal' && !isset($this->fieldConfig['template'])) {
			$this->fieldConfig['template'] = "{label}\n{beginWrapper}\n{input}\n{error}\n{endWrapper}\n{hint}";
		}
		parent::init();
	}

	public function fieldReadonly($label, $value, $options = []) {
		$isHorizontal = $this->layout === 'horizontal';
		$groupOptions = ['class' => $isHorizontal ? 'row mb-2' : 'mb-2'];
		$labelOptions = ['class' => $isHorizontal ? 'col-form-label' : 'form-label'];
		$wrapperOptions = [];
		
		if (!isset($options['class'])) {
			$options['class'] = 'form-control';
		}
		else {
			$options['class'] = 'form-control '.$options['class'];
		}
		$options['readonly'] = 'readonly';

		if ($isHorizontal) {
			$labelOptions['class'] = 'col-sm-2 col-form-label text-sm-end fw-semibold pe-sm-3';
			$wrapperOptions['class'] = 'col-sm-8';
		}

		$html = Html::beginTag('div', $groupOptions);
		$html .= Html::label($label, null, $labelOptions);
		$html .= Html::beginTag('div', $wrapperOptions);
		$html .= Html::textInput('', $value, $options);
		$html .= Html::endTag('div');
		$html .= Html::endTag('div');

		return $html;
	}

	public function fieldResourceImg($label, $src, $options = []) {
		$isHorizontal = $this->layout === 'horizontal';
		Html::addCssClass($options, $isHorizontal ? 'row mb-3' : 'mb-3');
		$labelOptions = ['class' => $isHorizontal ? 'col-form-label' : 'form-label'];
		$wrapperOptions = [];
		if ($isHorizontal) {
			$fieldConfig = $this->fieldConfig['horizontalCssClasses'];
			$labelOptions['class'] = $labelOptions['class'].' '.$fieldConfig['label'];
			$wrapperOptions['class'] = $fieldConfig['wrapper'];
		}

		$html = Html::beginTag('div', $options);
		$html .= Html::label($label, null, $labelOptions);
		$html .= Html::beginTag('div', $wrapperOptions);
		$html .= Html::resourceImg($src, ['width'=>'100%', 'class'=>'img-show']);
		$html .= Html::endTag('div');
		$html .= Html::endTag('div');

		return $html;
	}
}
