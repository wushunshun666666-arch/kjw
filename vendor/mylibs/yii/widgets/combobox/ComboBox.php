<?php
/**
 * ComboBox.php
 * @author suxh
 */

namespace mylibs\yii\widgets\combobox;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;

class ComboBox extends Widget
{
	public $name = 'combobox';

	/**
	 * @var string 当前选中项
	 */
	public $selection = '';
	/**
	 * @var array
	 */
	public $items = [];

	/**
	 * @var string|array 如果配置URL，则组合框选项通过ajax到该URL地址获取
	 */
	public $searchUrl = [];
	public $searchName = 'query';
	public $valueField = 'id';
	public $labelField = 'name';
	public $searchField = 'name';
	public $sortField = 'name';
	public $maxOptions = 2000;
	public $placeholder = '';

	public $options = ['class' => 'combobox form-select'];

	public function init() {
		if(isset($this->options['id'])) {
			$id=$this->options['id'];
		}
		else {
			$id=$this->options['id']=$this->getId();
		}

		// 发布资源文件
		$view = $this->getView();
		ComboBoxAsset::register($view);

		// 注册JS脚本
		$options = $this->getClientOptions();
		$options = Json::htmlEncode($options);

		$this->registerJs($view, $options);
	}

	/**
	 * Ends running the widget.
	 */
	public function run() {
		echo Html::dropDownList($this->name, $this->selection, $this->items, $this->options);
	}

	protected function getClientOptions() {
		$options = [];
		$options['maxOptions'] = $this->maxOptions;
		$options['placeholder'] = $this->placeholder;
		$options['wrapperClass'] = 'bootstrap5-combobox';
		// 原始 select 带有 Bootstrap 5 的 form-select 类，不能复制到下拉菜单，
		// 否则菜单容器会再次出现原生下拉箭头和输入框内边距。
		$options['copyClassesToDropdown'] = false;

		if ($this->searchUrl) {
			if (is_array($this->searchUrl)) {
				$this->searchUrl = Url::to($this->searchUrl);
			}

			$render = new JsExpression("{
				option: function(item, escape) {
					return '<div>' + escape(item.{$this->labelField}) + '</div>';
				}
			}");

			$load = new JsExpression("function(query, callback) {
				if (!query.length) return callback();
				$.ajax({
					url: '{$this->searchUrl}?query=' + encodeURIComponent(query),
					type: 'GET',
					dataType: 'json',
					error: function() {
						callback();
					},
					success: function(res) {
						callback(res.data);
					}
				});
			}");

			$options['valueField'] = $this->valueField;
			$options['labelField'] = $this->labelField;
			$options['searchField'] = $this->searchField;
			$options['sortField'] = $this->sortField;
			$options['create'] = false;
			$options['render'] = $render;
			$options['load'] = $load;
		}

		return $options;
	}

	protected function registerJs($view, $options) {
		$id=$this->options['id'];
		$view->registerJs("$(document).ready(function() {\$('#{$id}').selectize({$options})})");
	}
}
