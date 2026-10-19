<?php
/**
 * Table.php
 * @author suxh
 */


namespace app\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class Table extends Widget
{
	/**
	 * @var array 列表标题
	 */
	public $head = [];
	public $dataProvider = null;
	public $itemView = null;
	/**
	 * @var array HTML属性
	 */
	public $options = ['class'=>'table table-striped table-bordered table-hover table-sm data-table'];
	/**
	 * @var string 数据为空时，提示文字
	 */
	public $emptyText = null;	
	public $viewData = [];
	
	/**
	 * (non-PHPdoc)
	 * @see CWidget::init()
	 */
	public function init() {
		$this->options['id'] = $this->getId();
		if ($this->emptyText === null) {
			$this->emptyText = '没有满足条件的记录！';
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see CWidget::run()
	 */
	public function run() {
		$this->renderContent();
	}

	/**
	 * 渲染列表内容
	 */
	protected function renderContent() {
		echo Html::beginTag('table', $this->options),"\n";
		$this->renderTableHead();
		$this->renderTableBody();
		echo Html::endTag('table'), "\n";
	}

	/**
	 * 渲染表格头部标题行
	 */
	protected function renderTableHead() {
		if (!empty($this->head)) {
			echo Html::beginTag('thead'),"\n";
			echo Html::beginTag('tr'),"\n";

			foreach($this->head as $name=>$options) {
				if(is_string($options)) {
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
		if (is_array($this->dataProvider)) {
			$data = $this->dataProvider;
		}
		else {
			$data = $this->dataProvider->getModels();
		}

		echo Html::beginTag('tbody'),"\n";
		if(count($data) > 0) {
			$view = $this->getView();
			foreach($data as $i=>$item)	{
				$data= $this->viewData;
				$data['index']=$i;
				$data['data']=$item;
			
				echo $view->render($this->itemView, $data);
			}			
		}
		else {
			$colsNum = count($this->head);
			$colsNum = ($colsNum<1)? 1 : $colsNum;
			echo "<tr><td colspan='{$colsNum}'>{$this->emptyText}</td></tr>";
		}
		
		echo Html::endTag('tbody'), "\n";
	}
}
