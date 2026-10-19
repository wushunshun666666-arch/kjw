<?php
/**
 * TabMenu.php
 * @author suxh
 */

namespace app\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;

class TabMenu extends Widget
{
	/**
	 * @var array
	 */
	public $menus = [];
	public $active = null;

	/**
	 * (non-PHPdoc)
	 * @see Widget::init()
	*/
	public function init() {
		echo Html::beginTag('ul', ['class' => 'nav nav-tabs']);

		foreach ($this->menus as $name => $route) {
			$url = Url::to($route);
			$active = $name == $this->active;
			echo Html::beginTag('li', ['class' => 'nav-item']);
			echo Html::a($name, $url, ['class' => 'nav-link'.($active ? ' active' : ''), 'aria-current' => $active ? 'page' : null]);
			echo Html::endTag('li');
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Widget::run()
	 */
	public function run() {
		echo Html::endTag('ul');
	}
}
