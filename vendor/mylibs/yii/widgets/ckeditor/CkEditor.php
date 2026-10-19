<?php
/**
 * CkEditor.php
 * @author suxh
 */

namespace mylibs\yii\widgets\ckeditor;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;

class CkEditor extends Widget
{
	public $name;
	public $value;
	public $options = ['class'=>'ckeditor'];
	public $clientOptions = [];

	public function init() {
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}
	}

	public function run() {
		echo Html::textarea($this->name, $this->value, $this->options);

		$view = $this->getView();
		CkEditorAsset::register($view);

		$options = $this->clientOptions;
		$options = Json::htmlEncode($options);

		$id = $this->options['id'];
		$view->registerJs("CKEDITOR.replace('{$id}', {$options});");
	}
}