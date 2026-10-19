<?php
/**
 * 数据列表
 * @author suxh
 */

namespace app\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;

class DataTableView extends Widget
{
	/**
	 * @var array 列表标题
	 */
	public $head = [];
	public $fixedHead = false;

	/**
	 * @var string|array 列表数据加载地址
	 */
	public $url = '';

	/**
	 * @var boolean 页面加载完成后，是否自动加载列表数据
	 */
	public $load = false;

	/**
	 * 页面加载完后回调
	 */
	public $afterLoad = null;

	public $post = null;

	public $clientOptions = [];

	public $prompt = '&nbsp;';

	/**
	 * @var string 视图容器标签，默认为div
	 */
	public $tagName = 'div';

	/**
	 * @var array 视图容器标签，对应的HTML属性
	 */
	public $options = [];
	public $bodyOptions = ['class'=>'overlay-wrapper data-table-content'];
	public $footerOptions = [];

	/**
	 * @var array 表格table标签对应的HTML属性
	 */
	public $tableOptions=['class'=>'table table-striped table-bordered table-hover table-sm data-table'];

	public $pager = 'app\widgets\LinkPager';

	/**
	 * @var string 分页部件容器标签对应的HTML属性
	 */
	public $pagerOptions=[
		'id'=>'pagination',
		'options'=>['class'=>'data-table-paginate'],
	];

	/**
	 * (non-PHPdoc)
	 * @see CWidget::init()
	 */
	public function init() {
		$this->options['id'] = $this->getId();
		if ($this->load) {
			$this->options['data-table-loading'] = '1';
		}

		$pagerId = $this->pagerOptions['options']['id'] ?? $this->pagerOptions['id'] ?? 'pagination';
		$this->pagerOptions['id'] = $pagerId;
		$this->pagerOptions['options']['id'] = $pagerId;

		$this->url = Url::to($this->url);
	}

	/**
	 * (non-PHPdoc)
	 * @see CWidget::run()
	 */
	public function run() {
		$this->registerClientScript();

		$this->renderContent();
	}

	/**
	 * 注册客户端脚本(js或css样式表)
	 */
	protected function registerClientScript() {
		// 注册JS脚本
		$options = $this->clientOptions;
		$options['url']			= $this->url;
		$options['load']		= $this->load;
		$options['pagerId']		= $this->pagerOptions['options']['id'];
		$options['post']		= $this->post;
		$options['afterLoad']	= $this->afterLoad;
		$options['fixedHead']	= $this->fixedHead;

		$options = Json::htmlEncode($options);
		$id = $this->getId();
		$js = "seajs.use('admin.data-table', function(datatable) {\$('#$id').dataTable($options);});";

		$view = $this->getView();
		$view->registerJs($js);
	}

	/**
	 * 渲染列表内容
	 */
	protected function renderContent() {
		echo Html::beginTag($this->tagName,$this->options)."\n";

		if ($this->fixedHead) {
			$options = ['class' => 'table-fixed-head'];
			echo Html::beginTag('div', $options),"\n";
			echo Html::beginTag('table', $this->tableOptions),"\n";
			$this->renderTableHead();
			echo Html::endTag('table'), "\n";
			echo Html::endTag('div'), "\n";
		}

		$bodyOptions = $this->bodyOptions;
		$bodyOptions['id'] = 'tableContent';
		echo Html::beginTag('div', $bodyOptions), "\n";
		echo Html::beginTag('table', $this->tableOptions),"\n";
		$this->renderTableHead();
		$this->renderTableBody();
		echo Html::endTag('table'), "\n";
		echo Html::endTag('div'), "\n";

		$this->renderFooter();

		echo Html::endTag($this->tagName),"\n";
	}

	/**
	 * 渲染表格头部标题行
	 */
	protected function renderTableHead() {
		if (!empty($this->head)) {
			echo Html::beginTag('thead'),"\n";
			echo Html::beginTag('tr'),"\n";

			foreach($this->head as $name=>$options) {
				if (isset($options['type']) && $options['type'] == 'checkbox') {
					unset($options['type']);
					$checkboxOptions = ['id'=>'checkbox_all'];
					$content = Html::checkBox('checkbox_all', false, $checkboxOptions).$name;
				}
				else if(is_string($options)) {
					$content = $options;
					$options = [];
				}
				else {
					$content = $name;
				}

				echo Html::tag('th', $content, $options), "\n";
			}

			echo Html::endTag('tr'),"\n";
			echo Html::endTag('thead'), "\n";
		}
	}

	/**
	 * 渲染表格内容行
	 */
	protected function renderTableBody() {
		$colsNum = count($this->head);
		$colsNum = ($colsNum<1)? 1 : $colsNum;

		echo Html::beginTag('tbody'),"\n";
        echo "<tr><td colspan='{$colsNum}'>{$this->prompt}</td></tr>";
		echo Html::endTag('tbody'), "\n";
	}

	/**
	 * 渲染表格底部
	 */
	protected function renderFooter() {
		if ($this->pager) {
			echo Html::beginTag('div', $this->footerOptions);

			$class = $this->pager;
			echo $class::widget($this->pagerOptions);

			echo Html::endTag('div');
		}
	}
}
