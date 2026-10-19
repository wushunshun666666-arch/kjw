<?php
/**
 * SetMap.php
 * @author suxh
 */

namespace mylibs\yii\widgets\viewmap;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

class SetMap extends Widget
{
	public $ak = 'q9aBGMCjeMQVuGrAYpCxyewL';
	/**
	 * @var array
	 */
	public $name;
	public $value;
	public $region;
	public $mapHeight;
	public $options = [];

	public function init() {
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}
		if (empty($this->mapHeight)) {
			$this->mapHeight = '360px';
		}

		// 发布资源文件
		$view = $this->getView();
		SetMapAsset::$appkey = $this->ak;
		$this->assetBundle = SetMapAsset::register($view);

		// 注册JS脚本
		$options = $this->getClientOptions();

		$options = Json::htmlEncode($options);

		$this->registerJs($view, $options);
	}

	/**
	 * Ends running the widget.
	 */
	public function run() {
		echo Html::beginTag('div', $this->options);
		echo Html::hiddenInput($this->name,$this->value);

		echo Html::beginTag('div', ['class'=>'d-flex align-items-center gap-2 mb-2'])."\n";
		$options = [];
		$options['id'] = $this->getId().'_suggestld';
		$options['class'] = 'form-control w-auto';
		$options['placeholder'] = '输入地址查询';
		$options['size'] = 50;
		echo Html::input('text','adress','',$options);
		echo Html::tag('span','拖动地图将您的准确位置，置于红十字的中心',['class'=>'text-warning']);
		echo Html::endTag('div');

		echo Html::beginTag('div', ['style'=>"width:100%;height:{$this->mapHeight};position:relative;"]);

		$imgUrl = $this->getAssetUrl('image/map_center.png');
		echo Html::img($imgUrl, ['style'=>'left:50%;position:absolute;margin:-20px 0 0 -20px;top:50%;z-index: 100;']);
		$options = ['id' => $this->getId().'_map', 'style'=>'width:100%;height:100%'];
		echo Html::tag('div','', $options);
		echo Html::endTag('div');
	}

	protected function getAssetUrl($asset) {
		$view = $this->getView();
		$manager = $view->getAssetManager();
		return $manager->getAssetUrl($this->assetBundle, $asset);
	}

	protected function getClientOptions() {
		$options = [];
		$options['ak'] = $this->ak;
		$options['name'] = $this->name;
		$options['value'] = $this->value;
		$options['region']  = $this->region;
		return $options;
	}

	protected function registerJs($view, $options) {
		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').setMap({$options})");
	}

	private $assetBundle;
}
