<?php
/**
 * DataTableData.php
 *
 * @author suxh
 */

namespace app\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class DataTableData extends Widget
{
	/**
	 * @var IDataProvider 视图数据源
	 */
	public $dataProvider;

	public $controller;

	/**
	 * @var string 列表行的视图名称
	 */
	public $itemView;

	public $emptyView = null;

	public $emptyData = [];

	public $emptyText = null;

	public $viewData = [];

	public $additionalData = [];

	/**
	 * (non-PHPdoc)
	 * @see CWidget::init()
	 */
	public function init() {
	}

	/**
	 * (non-PHPdoc)
	 * @see CWidget::run()
	 */
	public function run() {
		echo $this->renderContent();
	}

	/**
	 * 渲染列表内容
	 */
	protected function renderContent() {
		$data = $this->dataProvider->getModels();
		$pager= $this->createPager();
		if(count($data)>0) {
			$body = $this->createBody();
			$data = [
				'empty'=>false,
				'count'=>count($data),
				'bodyHtml'=>$body,
				'pager'=>$pager,
				'additional' => $this->additionalData,
			];
		}
		else {
			$html = $this->createEmpty();
			$data = [
				'empty'=>true,
				'emptyHtml'=>$html,
				'pager'=>$pager,
				'additional' => $this->additionalData,
			];
		}

		return $this->controller->jsonSuccess($data);
	}

	/**
	 * 生成分页内容
	 */
	protected function createPager() {
		$page = $this->dataProvider->getPagination();
		if (!$page) {
			return false;
		}

		return [
			'currentPage'=>$page->getPage(),
			'pageCount'=>$page->getPageCount(),
			'itemCount'=>$page->totalCount,
			'pageSize'=>$page->getPageSize(),
			'pageVar'=>$page->pageParam,
		];
	}

	/**
	 * 生成表格内容
	 */
	protected function createBody() {
		$data = $this->dataProvider->getModels();
		$table = '';
		foreach($data as $i=>$item)	{
			$data= $this->viewData;
			$data['index']=$i;
			$data['data']=$item;

			$content = $this->controller->renderAjax($this->itemView, $data);

			$table .= $content;
		}

		return $table;
	}

	protected function createEmpty() {
		if ($this->emptyView) {
			return $this->controller->render($this->emptyView,$this->emptyData,true);
		}
		else {
			if ($this->emptyText === null) {
				$this->emptyText = '没有满足条件的记录，您可以尝试其它查询条件！';
			}

			return Html::tag('div',$this->emptyText,array('class'=>'noSearchResult'));
		}
	}
}
