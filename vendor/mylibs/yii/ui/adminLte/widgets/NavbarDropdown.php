<?php
/**
 * NavbarDropdown.php
 * @author suxh
 */

namespace mylibs\yii\ui\adminLte\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

class NavbarDropdown extends Widget
{
	public $label = '';
	public $items = [];
	public $options = ['class'=>'nav-item dropdown'];
	public $clientOptions = [];

	public function init() {
		if(isset($this->options['id'])) {
			$id=$this->options['id'];
		}
		else {
			$id=$this->options['id']=$this->getId();
		}

		// 注册JS脚本
		$options = $this->getClientOptions();
		$options = Json::htmlEncode($options);

		$this->registerJs($options);
	}

	/**
	 * Ends running the widget.
	 */
	public function run() {
		echo Html::beginTag('li', $this->options);

		$this->renderButton();
		$this->renderActions();

		echo Html::endTag('li');
	}

	protected function renderButton() {
		$options = [
			'class' => 'nav-link dropdown-toggle',
			'data-bs-toggle' => 'dropdown',
			'role' => 'button',
			'aria-haspopup' => 'true',
			'aria-expanded' => 'false',
		];

		echo Html::beginTag('a', $options);
		echo $this->label;
		echo Html::endTag('a');
	}

	protected function renderActions() {
		echo Html::beginTag('ul', ['class' => 'dropdown-menu']);

		foreach ($this->items as $id=>$name) {
			echo Html::beginTag('li');
			echo Html::a($name, 'javascript:void(0);', ['class'=>'dropdown-item', 'value'=>$id]);
			echo Html::endTag('li');
		}

		echo Html::endTag('ul');
	}

	protected function getClientOptions() {
		$options = $this->clientOptions;
		return $options;
	}

	protected function registerJs($options) {
		$initJs = <<<Js
$.fn.navbarDropdown = function(options) {
	return this.each(function() {
		var navbarDropdown = new NavbarDropdown(this, options);
		$(this).data('navbarDropdown', navbarDropdown);
	});
};
function NavbarDropdown(container, options) {
	var \$container = $(container);

	var onSelect = function(value) {
		if (options.onSelect) {
			options.onSelect(value);
		}
	};

	\$container.find('li > a').click(function() {
		onSelect(\$(this).attr('value'));
	});

	return {
	};
}
Js;
		// 发布资源文件
		$view = $this->getView();
		$view->registerJs($initJs);

		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').navbarDropdown({$options})");
	}
}
