<?php
/**
 * Printer.php
 *
 * @author James
 */

namespace mylibs\pdf;

abstract class Printer
{
	/**
	 *
	 * @param array $options 非必填
	 *	page			打印纸张格式，默认A4
	 *	orientation		打印纸张方向，默认P（竖向）
	 *	title			标题
	 *	unit			PDF尺寸单位，默认mm
	 *	font			字体，默认droidsansfallback
	 *
	 *	topMargin		顶部间距-内容区域
	 *	rightMargin		右部间距-内容区域
	 *	bottomMargin	低部间距-内容区域
	 *	leftMargin		左部间距-内容区域
	 *	headerMargin	顶部间距-页头区域
	 *	footerMargin	低部间距-页尾区域
	 * @param string $page
	 * @param string $orientation
	 */
	public function __construct($options=[]) {
		$this->pdf = new PDF($options);
		$this->pdf->setHeaderCallback(function() {$this->header();});
	}

	public function output() {
		$this->pdf->newPageGroup();
		$this->body();
		$this->pdf->output();
	}

	protected function header() {
		$this->pdf->defaultHeader();
	}

	protected function footer() {}

	abstract protected function body();

	protected $pdf;
}