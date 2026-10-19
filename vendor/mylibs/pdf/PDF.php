<?php
/**
 * PDF.php
 *
 * @author James
 */

namespace mylibs\pdf;
require_once(dirname(__FILE__).'/../../tcpdf/tcpdf.php');

class PDF
{
	public function __construct($options) {
		$page			= isset($options['page']) ? $options['page'] : 'A4';
		$unit			= isset($options['unit']) ? $options['unit'] : PDF_UNIT;
		$orientation	= isset($options['orientation']) ? $options['orientation'] : 'P';
		$font			= isset($options['font']) ? $options['font'] : 'droidsansfallback';
		$this->orientation = $orientation;
		$this->pageFormate = $page;
		$this->font = $font;

		// 初始化PDF
		$this->pdf = new _PDF($orientation, $unit, $page);
		$topMargin    = isset($options['topMargin'])    ? $options['topMargin']    : PDF_MARGIN_TOP;
		$rightMargin  = isset($options['rightMargin'])  ? $options['rightMargin']  : PDF_MARGIN_RIGHT;
		$leftMargin   = isset($options['leftMargin'])   ? $options['leftMargin']   : PDF_MARGIN_LEFT;
		$headerMargin = isset($options['headerMargin']) ? $options['headerMargin'] : PDF_MARGIN_HEADER;
		$footerMargin = isset($options['footerMargin']) ? $options['footerMargin'] : PDF_MARGIN_FOOTER;

		$title = isset($options['title']) ? $options['title'] : '打印订单';
		$this->pdf->SetTitle($title);
		$this->pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', 10));
		$this->pdf->setPrintFooter(false);

		// set margins
		$this->pdf->SetMargins($leftMargin, $topMargin, $rightMargin, true);
		$this->pdf->SetHeaderMargin($headerMargin);
		$this->pdf->SetFooterMargin($footerMargin);

		// set auto page breaks
		$this->pdf->SetAutoPageBreak(TRUE, 1);

		// set image scale factor
		$this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		// set font
		$this->fontSize();

		$this->pdf->SetDisplayMode('fullpage');
	}

	public function setHeaderCallback($callback) {
		$this->pdf->setHeaderCallback($callback);
	}

	/**
	 * 添加code39码
	 * @param string  $code
	 * @param integer $x
	 * @param integer $y
	 * @param string  $w  eg:'50%'
	 * @param integer $h
	 * @param $crude (int) 条码粗细
	 */
	public function code39($code, $x='', $y='', $width='', $height=20, $crude='') {
		$this->pdf->write1DBarcode($code, 'C39', $x, $y, $this->getRealWitdh($width), $height, $crude, $this->barcodeStyle);
		$this->newRow();
	}

	/**
	 * 添加code128码
	 * @param string  $code
	 * @param integer $x
	 * @param integer $y
	 * @param string  $w  eg:'50%'
	 * @param integer $h
	 * @param $crude (int) 条码粗细
	 */
	public function code128($code, $x='', $y='', $width='', $height=20, $crude='') {
		$this->pdf->write1DBarcode($code, 'C128', $x, $y, $this->getRealWitdh($width), $height, $crude, $this->barcodeStyle);
		$this->newRow();
	}

	/**
	 * 大标题-默认居中
	 * @param string $title
	 * @param number $size
	 */
	public function title($title, $align='C', $size=18) {
		$this->fontSize($size);
		$this->pdf->Cell(0, $this->lineHeight, $title, 0, 1, $align);
		$this->fontSize();
		$this->newRow(2);
	}

	/**
	 * 副标题
	 * @param unknown $data
	 */
	public function subTitle($data) {
		$this->newRow(1);
		$this->row($data, false, 14);
	}

	/**
	 * 空白行
	 */
	public function newRow($height='') {
		$this->pdf->Ln($height);
	}

	/**
	 * 添加备注框
	 */
	public function note($height=20) {
		$width = $this->getUseWidth();
		$this->pdf->Cell($width, $height, '备注', 1, 0, 'L', 0);
		$this->newRow();
	}

	/**
	 * 横线
	 */
	public function line() {
		$this->pdf->SetLineStyle(array('cap' => 'butt', 'join' => 'miter', 'dash' => 0));
		$this->pdf->Cell($this->getUseWidth(), 0, '', 'T', 0, 'C');
		$this->newRow();
	}

	/**
	 * 添加一行文本
	 * @param mixed   $data string|array [['width?'=>'width', 'value'=>'value', 'align'=>'align']] 宽度默认等分
	 * @param boolean $border
	 * @param integer $size
	 */
	public function row($data, $border=false, $size=null) {
		if ($size) {$this->fontSize($size);}

		$data = is_array($data) ? $data : [['value' => $data]];
		$defaultWidth = 100/count($data);
		$maxHeight = $this->lineHeight;
		foreach ($data as $item) {
			$width = isset($item['width']) ? $item['width'] : $defaultWidth;
			$width = $this->getRealWitdh($width);
			$text = $item['value'];
			$height = $this->pdf->getStringHeight($width, $text);
			if ($height > $maxHeight) $maxHeight = $height;
		}

		// 判断分页
		$y = $this->pdf->GetY();
		if (($y + $maxHeight) >= $this->getUseHeight()) {
			$this->newPage($this->orientation, $this->pageFormate);
			$y = $this->getTopMargin();
		}

		foreach ($data as $item) {
			$width = isset($item['width']) ? $item['width'] : $defaultWidth;
			$align = isset($item['align']) ? $item['align'] : 'L';
			$value = $item['value'];
			$this->col($value, $width, $align, $border, $maxHeight);
		}

		if ($size) {$this->fontSize();}
		$this->newRow();
	}

	/**
	 * 添加列
	 * @param string $text  string
	 * @param string $width 宽度，默认自适应
	 * @param string $align 对齐方式，默认左对齐
	 * @param integer $heith 高度，默认自适应
	 * @param boolean $border 边框，false-无，true-有
	 */
	public function col($text, $width=0, $align='L', $border=false, $height=0) {
		$height = ($height) ? $height : $this->lineHeight;
		$width = ($width) ? $this->getRealWitdh($width) : 0;
		$border = ($border) ? 1 : 0;
		$this->pdf->MultiCell($width, $height, $text, $border, $align, false, 0);
	}

	/**
	 * 表格添加一行
	 * @param unknown $data
	 */
	public function tableRow($data) {
		$this->row($data, true);
	}

	/**
	 * 添加表格-自动换行
	 * @param array $headers eg: [['width'=>'width1', 'value'=>'value1'], ['width'=>'width2', 'value'=>'value2']]
	 * @param array $data eg:[['value1-1', 'value1-2'],['value2-1', 'value2-2']]
	 */
	public function table($headers,$data) {
		// Header
		$useWidth = $this->getUseWidth();
		$cols = count($headers);
		foreach ($headers as $header) {
			$width = isset($header['width']) ? $header['width'] : 0;
			$align = isset($header['align']) ? $header['align'] : 'L';

			$this->col($header['name'], $width, $align, true);
		}
		$this->newRow();

		// Data
		foreach($data as $row) {
			$lineDate = [];
			for ($i=0; $i<$cols; $i++) {
				$header = $headers[$i];
				$width = isset($header['width']) ? $header['width'] : 0;
				$align = isset($header['align']) ? $header['align'] : 'L';
				$lineDate[] = [
					'value'	=> $row[$i],
					'width'	=> $width,
					'align'	=> $align
				];
			}

			$this->row($lineDate, true);
		}
	}

	/**
	 * html
	 * @param string $html
	 */
	public function html($html) {
		$this->pdf->writeHTML($html);
	}

	/**
	 * 宽度比重 => 实际宽度
	 * @param string $width eg:50%
	 */
	protected  function getRealWitdh($width) {
		$width = (int)str_replace('%', '', $width);
		return $this->getUseWidth()*$width/100;
	}

	/**
	 * 设置字体大小
	 * @param number $size
	 */
	public function fontSize($size = 10) {
		$this->pdf->SetFont($this->font, '', $size);
	}

	public function defaultHeader() {
		$pageStr = $this->pageTextInGroup();
		$this->row([
			['value' => \Yii::$app->params['companyName']]
		]);
		$this->line();
	}

	/**
	 * 当前页码
	 * @return string
	 */
	public function pageText() {
		return '页数：'.$this->pdf->getAliasNumPage().' / '.$this->pdf->getAliasNbPages();
	}

	/**
	 * 当前分组页码
	 * @return string
	 */
	public function pageTextInGroup() {
		return '页数：'.$this->pdf->getPageNumGroupAlias().' / '.$this->pdf->getPageGroupAlias();
	}

	public function setTopMargin($margin) {
		$this->pdf->SetTopMargin($margin);
	}

	public function currentGroup() {
		return $this->pdf->getCurrengPageGroup();
	}

	/**
	 * 当前页码
	 */
	public function currentPage() {
		return $this->pdf->getAliasNumPage();
	}

	/**
	 * 总页数
	 * @return string
	 */
	public function totalPage() {
		return $this->pdf->getAliasNbPages();
	}

	/**
	 * 当前分组页码
	 * @return alias
	 */
	public function currentPageInGroup() {
		return $this->pdf->getPageNumGroupAlias();
	}

	/**
	 * 当前分组总页数
	 */
	public function totalPageInGroup() {
		return $this->pdf->getPageGroupAlias();
	}

	/**
	 * 添加新组
	 */
	public function newPageGroup() {
		$this->pdf->startPageGroup();
		$this->newPage();
	}

	/**
	 * 添加新页
	 */
	public function newPage() {
		$this->pdf->AddPage($this->orientation, $this->pageFormate);
	}

	public function output() {
		// auto print
		$this->pdf->IncludeJS("this.print()");
		// Close and output PDF document
		$this->pdf->Output('mancando.pdf', 'I');
	}

	/**
	 * 可用宽，不含边距
	 */
	private function getUseWidth() {
		$margins = $this->pdf->getMargins();
		$width = $this->pdf->getPageWidth();
		return $width-$margins['left'] - $margins['right'];
	}

	/**
	 * 可用高，不含边距
	 */
	private function getUseHeight() {
		$height = $this->pdf->getPageHeight();
		$margins = $this->pdf->getMargins();
		return $height-$margins['footer'];
	}

	private function getTopMargin() {
		$margins = $this->pdf->getMargins();
		return $margins['top'];
	}

	private $pdf;
	private $tableHeaderHeight = 8;
	private $lineHeight = 0;  // 自适应高
	private $orientation;
	private $pageFormate;
	private $font;
	private $barcodeStyle = array(
		'position' => '',
		'align' => 'C',
		'stretch' => false,
		'fitwidth' => true,
		'cellfitalign' => '',
		'border' => false,
		'hpadding' => 'auto',
		'vpadding' => 'auto',
		'fgcolor' => array(0,0,0),
		'bgcolor' => false, //array(255,255,255),
		'text' => true,
		'font' => 'helvetica',
		'fontsize' => 8,
		'stretchtext' => 4
	);
}

class _PDF extends \TCPDF
{
	public function __construct($orientation, $unit, $page) {
		parent::__construct($orientation, $unit, $page);
	}

	public function setHeaderCallback($callback) {
		$this->headerCallback = $callback;
	}

	public function Header() {
		$callback = $this->headerCallback;
		$callback();
	}

	public $headerCallback;
}
