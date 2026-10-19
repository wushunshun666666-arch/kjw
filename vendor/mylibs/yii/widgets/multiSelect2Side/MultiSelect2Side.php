<?php
/**
 * MultiSelect2Side.php
 * @author suxh
 */

namespace mylibs\yii\widgets\multiSelect2Side;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

class MultiSelect2Side extends Widget
{
	/**
	 * @var array
	 */
	public $data = [];
	public $name = 'select[]';
	public $selectableTitle = '';
	public $selectionTitle = '';

	public $clientOptions = [];

	public $inputOptions = [];
	public $options = ['class' => 'ms-container'];

	public function init() {
		if(!isset($this->options['id'])) {
			$this->options['id']=$this->getId();
		}

		// 发布资源文件
		$view = $this->getView();
		MultiSelect2SideAsset::register($view);

		// 注册JS脚本
		$options = $this->getClientOptions();
		$options = Json::htmlEncode($options);

		$this->registerJs($view, $options);
	}

	/**
	 * Ends running the widget.
	 */
	public function run() {
		echo Html::beginTag('div', $this->options)."\n";

		$options = $this->inputOptions;
		$options['name'] = $this->name;
		$options['multiple'] = 'multiple';
		$options['style'] = 'display:none;';
		echo Html::beginTag('select', $options);
		foreach ($this->data as $item) {
			$options = [];
			if ($item['selected']) {
				$options['selected'] = 'selected';
			}
			$options['value'] = $item['id'];
			echo Html::tag('option', $item['name'], $options);
		}
		echo Html::endTag('select');

		echo Html::beginTag('div', ['class'=>'ms-selectable']);
		if (!empty($this->selectableTitle)) {
			echo Html::tag('div', $this->selectableTitle, ['class'=>'ms-title']);
		}
		echo Html::beginTag('ul', ['class'=>'ms-list']);
		foreach ($this->data as $item) {
			$style = ($item['selected'])? 'display:none;':'display: list-item;';
			$options = [
				'class'=>'ms-elem-selectable',
				'style'=>$style,
				'value'=>$item['id'],
			];
			echo Html::beginTag('li', $options);
			echo Html::tag('span', $item['name']);
			echo Html::endTag('li');
		}
		echo Html::endTag('ul');
		echo Html::endTag('div');

		echo Html::beginTag('div', ['class'=>'ms-selection']);
		if (!empty($this->selectionTitle)) {
			echo Html::tag('div', $this->selectionTitle, ['class'=>'ms-title']);
		}
		echo Html::beginTag('ul', ['class'=>'ms-list ms-focus']);
		foreach ($this->data as $item) {
			$style = ($item['selected'])? 'display: list-item;':'display:none;';
			$options = [
				'class'=>'ms-elem-selection',
				'style'=>$style,
				'value'=>$item['id']
			];
			echo Html::beginTag('li', $options);
			echo Html::tag('span', $item['name']);
			echo Html::endTag('li');
		}
		echo Html::endTag('ul');
		echo Html::endTag('div');

		echo Html::endTag('div');
	}

	protected function getClientOptions() {
		$options = $this->clientOptions;
		return $options;
	}

	protected function registerJs($view, $options) {
		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').multiSelect2Side({$options})");
	}
}
