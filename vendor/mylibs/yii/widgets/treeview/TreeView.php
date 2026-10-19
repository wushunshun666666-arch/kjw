<?php
/**
 * TreeView.php
 * @author suxh
 */

namespace mylibs\yii\widgets\treeview;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

class TreeView extends Widget
{
	/**
	 * @var array
	 */
	public $data;

	public $clientOptions=[];

	public $options = ['class' => 'treeview'];

	public $autoOpen = false;

	public function init() {
		if(isset($this->options['id'])) {
			$id=$this->options['id'];
		}
		else {
			$id=$this->options['id']=$this->getId();
		}

		// 发布资源文件
		$view = $this->getView();
		TreeViewAsset::register($view);

		// 注册JS脚本
		$options = $this->getClientOptions();
		$options = Json::htmlEncode($options);

		$this->registerJs($view, $options);
	}

	/**
	 * Ends running the widget.
	 */
	public function run() {
		echo Html::tag('div', '', $this->options)."\n";
	}

	protected function getClientOptions() {
		$options = $this->clientOptions;
		$options['data'] = $this->data;
		$options['autoOpen'] = $this->autoOpen;
		return $options;
	}

	protected function registerJs($view, $options) {
		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').tree({$options})");
	}
}
