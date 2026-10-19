<?php
/**
 * AutoComplete.php
 * @author suxh
 */

namespace mylibs\yii\widgets\autocomplete;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;

class AutoComplete extends Widget
{
	public $name = '';
	public $value = '';

	public $searchUrl = [];
	public $delay = 600;

	public $options = ['class' => 'form-control'];

	public function init() {
		if(isset($this->options['id'])) {
			$id=$this->options['id'];
		}
		else {
			$id=$this->options['id']=$this->getId();
		}

		// 发布资源文件
		$view = $this->getView();
		Asset::register($view);

		// 注册JS脚本
		$options = $this->getClientOptions();
		$options = Json::htmlEncode($options);

		$this->registerJs($view, $options);
	}

	public function run() {
		echo Html::textInput($this->name, $this->value, $this->options);
	}

	protected function getClientOptions() {
		$options = [];
		$source = new JsExpression("function(request, response) {
			var url = '{$this->searchUrl}';
			$.ajax({
                url: url,
                type: 'POST',
                data: {
                    query: request.term
                },
                dataType: 'json',
                success: function(data) {
                    response($.map(data.data, function(item, index) {
                        return item;
                    }));
                }
            });
        }");

		$options['source'] = $source;
		$options['delay'] = $this->delay;
		$options['autoFocus'] = true;

		return $options;
	}

	protected function registerJs($view, $options) {
		$id=$this->options['id'];
		$view->registerJs("\$('#{$id}').autocomplete({$options})");
	}
}