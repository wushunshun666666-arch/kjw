<?php
/**
 * Wizard.php
 * @author suxh
 */

namespace mylibs\yii\ui\adminLte\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class Wizard extends Widget
{
	public $steps = [];
	public $active = null;
	public $options = ['class' => 'wizard'];

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Widget::run()
	 */
	public function run() {
		echo Html::beginTag('div', $this->options);
		echo Html::beginTag('div', ['class' => 'wizard-inner']);
		echo Html::beginTag('ul', ['class' => 'steps']);

		foreach ($this->steps as $step=>$name) {
			$options = [];
			if ($step == $this->active) {
				$options['class'] = 'active';
			}
			echo Html::beginTag('li', $options);
			echo Html::tag('span', $step, ['class' => 'badge text-bg-primary']);
			echo Html::encode($name);
			echo Html::tag('span', '', ['class' => 'chevron']);
			echo Html::endTag('li');
		}

		echo Html::endTag('ul');
		echo Html::endTag('div');
		echo Html::endTag('div');
	}
}
