<?php
/**
 * ViewMap.php
 * @author suxh
 */

namespace mylibs\yii\widgets\viewmap;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

class ViewMap extends Widget
{
	public $ak = 'q9aBGMCjeMQVuGrAYpCxyewL';
	/**
	 * @var array
	 */
	public $center = [];

	public $markers = [];

	public $options = [];

	public function init() {
		if(isset($this->options['id'])) {
			$id=$this->options['id'];
		}
		else {
			$id=$this->options['id']=$this->getId();
		}

		// 发布资源文件
		$view = $this->getView();
		ViewMapAsset::$appkey = $this->ak;
		ViewMapAsset::register($view);

		// 注册JS脚本
		$options = $this->getClientOptions();
		$options = Json::htmlEncode($options);

		$this->registerJs($view, $options);
	}

	/**
	 * Ends running the widget.
	 */
	public function run() {
		echo Html::tag('div', '', $this->options);
	}

	protected function getClientOptions() {
		$options = [];
		$options['ak'] = $this->ak;
		$options['center'] = $this->center;
		$options['markers'] = $this->markers;
		return $options;
	}

	protected function registerJs($view, $options) {
		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').viewMap({$options})");
	}
}
