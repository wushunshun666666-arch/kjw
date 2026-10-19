<?php
/**
 * StatusGroup.php
 * @author suxh
 */

namespace mylibs\yii\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

class StatusGroup extends Widget
{
	public $active = '';
	public $groups = [];
	public $activeClass = 'btn-warning';
	public $options = [];
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
		echo Html::beginTag('span', $this->options);

		$buttons = $this->renderButtons();
		echo implode("\n", $buttons);

		echo Html::endTag('span');
	}

	protected function renderButtons() {
		$buttons = [];
		foreach ($this->groups as $id => $group) {
			$name = is_array($group)? $group['name']:$group;
			$items = is_array($group)? $group['items']:[];

			$class = 'btn-light';
			if ($id == $this->active || (count($items) > 1 && isset($items[$this->active]))) {
				$class = $this->activeClass;
			}

			if (count($items) > 1) {
				$buttons[] = $this->renderButtonDropdown($id, $name, $items, $class);
			}
			else {
				$buttons[] = $this->renderButton($id, $name, $class);
			}
		}

		return $buttons;
	}

	protected function renderButtonDropdown($id, $name, $items, $class) {
		$html = '';
		$html .= Html::beginTag('div', ['class'=>'btn-group']);
		$html .= Html::button($name, ['class' => 'btn '.$class, 'value'=>$id]);

		$options = [];
		$options['class'] 			= 'btn '.$class.' dropdown-toggle dropdown-toggle-split';
		$options['data-bs-toggle'] = 'dropdown';
		$options['aria-haspopup'] 	= 'true';
		$options['aria-expanded'] 	= 'false';
		$content = '<span class="visually-hidden">Toggle Dropdown</span>';
		$html .= Html::button($content, $options);

		$html .= '<ul class="dropdown-menu">';
		foreach ($items as $id => $name) {
			$html .= Html::beginTag('li');
			$html .= Html::tag('a', $name, ['class'=>'dropdown-item', 'href'=>'javascript:void(0);', 'value'=>$id]);
			$html .= Html::endTag('li');
		}
		$html .= '</ul>';

		$html .= Html::endTag('div');
		return $html;
	}

	protected function renderButton($id, $name, $class) {
		return Html::button($name, ['class' => 'btn '.$class, 'value'=>$id]);
	}

	protected function getClientOptions() {
		$options = $this->clientOptions;
		$options['defaultClass'] = 'btn-light';
		$options['activeClass'] = $this->activeClass;
		return $options;
	}

	protected function registerJs($options) {
		$initJs = <<<Js
$.fn.statusGroup = function(options) {
	return this.each(function() {
		var statusGroup = new StatusGroup(this, options);
		$(this).data('statusGroup', statusGroup);
	});
};
function StatusGroup(container, options) {
	var \$container = $(container);

	var setButtonActive = function(\$btn) {
		var \$active = \$container.find('.'+options.activeClass);
		\$active.removeClass(options.activeClass);
		\$active.addClass(options.defaultClass);
		\$btn.removeClass(options.defaultClass);
		\$btn.addClass(options.activeClass);
	};
	var onSelect = function(value) {
		if (options.onSelect) {
			options.onSelect(value);
		}
	};
	var setActive = function(value) {
		\$container.children('button').each(function() {
			if ($(this).val() == value) {
				setButtonActive(\$(this));
				return false;
			}
		});
	};

	\$container.children('button').click(function() {
		setButtonActive(\$(this));
		onSelect(\$(this).val());
	});
	\$container.find('.btn-group').find('button:first').click(function() {
		setButtonActive(\$(this).parent().find('button'));
		onSelect(\$(this).val());
	});
	\$container.find('.dropdown-menu').find('li').click(function() {
		setButtonActive(\$(this).parent().parent().find('button'));
		onSelect(\$(this).find('a').attr('value'));
	});

	return {
		setActive:setActive
	};
}
Js;
		// 发布资源文件
		$view = $this->getView();
		$view->registerJs($initJs);

		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').statusGroup({$options})");
	}
}
