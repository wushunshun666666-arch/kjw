<?php
/**
 * AlertInfo.php
 * @author suxh
 */

namespace mylibs\yii\ui\adminLte\widgets;

use yii\helpers\Html;

class AlertInfo extends \yii\bootstrap5\Alert
{
	public function init(): void
	{
		parent::init();
		echo '<h4><i class="icon fa fa-info-circle"></i>提示</h4>';
	}

	protected function initOptions(): void
	{
		parent::initOptions();
		Html::addCssClass($this->options, 'alert-info alert-dismissible');
	}
}
