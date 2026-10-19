<?php
/**
 * LinkPager.php
 *
 * @author suxh
 */

namespace app\widgets;

use yii\helpers\Html;

class LinkPager extends \yii\base\Widget
{
	const CSS_INFO_WRAPER = 'data-table-info-wrapper';
	const CSS_PAGINATE_WRAPER = 'data-table-pagination-wrapper';
	const CSS_INFO = 'data-table-info';
	const CSS_PAGINATE = 'data-table-pagination paging_simple_numbers';
	const CSS_PAGINATION = 'pagination-summary';
	const CSS_BUTTON_NEXT = 'paginate-button next';
	const CSS_BUTTON_PREV = 'paginate-button previous';
	const CSS_BUTTON_FIRST= 'paginate-button first';
	const CSS_BUTTON_LAST = 'paginate-button last';


	/**
	 * @var array Pager容器的HTML属性
	 */
	public $options = ['class'=>'data-table-paginate'];
	/**
	 * @var string 下一页按钮标签
	 */
	public $nextPageLabel = '下一页';

	/**
	 * @var string 上一页按钮标签
	 */
	public $prevPageLabel = '上一页';

	/**
	 * @var string 首页按钮标签
	 */
	public $firstPageLabel = '首页';

	/**
	 * @var string 末页按钮标签
	 */
	public $lastPageLabel = '末页';

	/**
	 * @var string
	 */
	public $header = '';

	/**
	 * @var string
	 */
	public $footer = '';

	/**
	 * (non-PHPdoc)
	 * @see CWidget::init()
	 */
	public function init() {
		if(!isset($this->options['id'])) {
			$this->options['id']=$this->getId();
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see CWidget::run()
	 */
	public function run() {
		echo $this->header;
		$this->renderContent();
		echo $this->footer;
	}

	/**
	 * 构造按钮
	 * @return array 返回按钮HTML代码
	 */
	protected function renderContent() {
		echo Html::beginTag('div',$this->options);

		$this->renderInfo();
		$this->renderPaginate();

		echo Html::endTag('div');
	}

	/**
	 * 构造记录数信息文本
	 * @return string 返回HTML代码
	 */
	protected function renderInfo() {
		echo Html::beginTag('div', ['class'=>self::CSS_INFO_WRAPER]);

		echo Html::beginTag('div', ['class'=>self::CSS_INFO, 'role'=>'status', 'aria-live'=>'polite']);
		echo '每页<b data-pager-page-size>0</b>条&nbsp;/&nbsp;共<b data-pager-item-count>0</b>条';
		echo Html::endTag('div');

		echo Html::endTag('div');
	}

	protected function renderPaginate() {
		echo Html::beginTag('nav', ['class'=>self::CSS_PAGINATE_WRAPER, 'aria-label'=>'列表分页']);

		echo Html::beginTag('div', ['class'=>self::CSS_PAGINATE]);
		$this->renderPagination();
		echo Html::endTag('div');

		echo Html::endTag('nav');
	}

	protected function renderPagination() {
		echo Html::beginTag('div', ['class'=>self::CSS_PAGINATION]);
		echo '<span class="pagination-current"><b data-pager-current-page>0</b><b>/</b><b data-pager-page-count>0</b>页</span>';
		$this->renderButtons();
		echo Html::endTag('div');
	}

	protected function renderButtons() {
		$this->renderButton($this->firstPageLabel, self::CSS_BUTTON_FIRST, 'first');
		$this->renderButton($this->prevPageLabel, self::CSS_BUTTON_PREV, 'prev');
		$this->renderButton($this->nextPageLabel, self::CSS_BUTTON_NEXT, 'next');
		$this->renderButton($this->lastPageLabel, self::CSS_BUTTON_LAST, 'last');
	}

	protected function renderButton($label, $class, $id) {
		echo Html::button($label, [
			'type'=>'button',
			'class'=>$class,
			'data-pager-action'=>$id,
			'disabled'=>true,
		]);
	}
}
