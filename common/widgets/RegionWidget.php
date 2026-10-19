<?php
/**
 * RegionWidget.php
 * @author suxh
 */

namespace common\widgets;

use common\models\common\Region;
use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Html;

class RegionWidget extends Widget
{
	/**
     * @var array ['id'=>['name'=>'','level'=>'','parent_id'=>''],]
     */
	public $regions = [];
	public $inputId = 'region';
	public $inputName = 'region';
	public $inputValue = null;
	public $selectLevel = 'any';
	public $emptyValue = '';
	public $emptyName = null;

	public $tag = 'div';
	public $options = ['class'=>'region-widget'];
	public $provinceOptions = [];
	public $cityOptions = [];
	public $districtOptions = [];

	/**
	 * @inheritdoc
	 */
	public function run() {
		// TODO
		$this->regions = Region::getAllAsTree();

		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}

		$this->renderContent();

		$this->registerClientScript();
	}

	protected function renderContent() {
		$html = [];
		$html[] = Html::beginTag($this->tag, $this->options);
		$html[] = Html::hiddenInput($this->inputName, $this->inputValue, ['id'=>$this->inputId]);
		$selectOptions = function ($options) {
			$options['class'] = trim(($options['class'] ?? '') . ' form-select');
			return $options;
		};
		$provinceItems = [];
		if ($this->emptyName !== null) {
			$provinceItems[$this->emptyValue] = $this->emptyName;
		}
		foreach ($this->regions as $id => $region) {
			$provinceItems[$id] = $region['name'];
		}
		$html[] = Html::dropDownList('', null, $provinceItems, $selectOptions($this->provinceOptions));
		$html[] = Html::dropDownList('', null, [], $selectOptions($this->cityOptions));
		$html[] = Html::dropDownList('', null, [], $selectOptions($this->districtOptions));
		$html[] = Html::endTag($this->tag);

		echo implode("\n", $html);
	}

	/**
	 * 注册客户端脚本(js或css样式表)
	 */
	protected function registerClientScript() {
		// 注册JS脚本
		$options = [];
		$options['regions'] = $this->regions;
		$options['value'] = $this->inputValue;
		$options['selectLevel'] = $this->selectLevel;
		$options['emptyValue'] = $this->emptyValue;
		$options['emptyName'] = $this->emptyName;

		$options = Json::htmlEncode($options);
		$id = $this->options['id'];
		$js = "(function(){var init=function(){if(window.jQuery && jQuery.fn.region){jQuery('#$id').region($options);return true;}return false;};if(!init()){seajs.use('wgt.region',function(){var n=0,t=setInterval(function(){if(init()||++n>50)clearInterval(t);},100);});}})();";

		$view = $this->getView();
		$view->registerJs($js);
	}
}
