<?php
/**
 * Code39Widget.php
 * @author James
 */

namespace common\widgets;

use yii\base\Widget;
use app\helpers\Html;
class Code39Widget extends Widget
{
	public $code;
	public $zoom = 1.8;
	public $height = 60;
	public $width = 600;

	/**
	 * @inheritdoc
	 */
	public function run() {
		$emptyPatten = $this->getPatten(0);
		$emptyDecode = $this->decode("*");
		$html = [];
		$html[] = Html::beginTag('table', [
			'aligh'		=> 'center',
			'width'		=> $this->width,
		]);
		$html[] = Html::beginTag('tr');
		$html[] = Html::beginTag('td');
		$html[] = Html::beginTag('table', [
			'widht'			=> $this->width,
			'height'		=> $this->height,
			'border'		=> 0,
			'cellspacing'	=> 0,
			'cellpadding'	=> 0,
			'align'			=> 'center',
		]);
		$html[] = Html::beginTag('tr');
		// 起始符
		$html[] = $this->renderCode($emptyDecode);
		$html[] = $this->renderItem($emptyPatten['color'], $emptyPatten['width']);
		// 数据符
		$code = (string)($this->code ?? '');
		for ($i=0;$i<strlen($code);$i++) {
			$html[] = $this->renderCode($this->decode($code[$i]));
			$html[] = $this->renderItem($emptyPatten['color'], $emptyPatten['width']);
		}
		// 终止符
		$html[] = $this->renderCode($emptyDecode);
		$html[] = Html::endTag('tr');
		$html[] = Html::endTag('table');
		$html[] = Html::endTag('td');
		$html[] = Html::endTag('tr');
		// 字符串
		$html[] = Html::beginTag('tr', [
			'style'	=> 'font-size:125%;font-weight:bold',
			'align'	=> 'center',
		]);
		$html[] = Html::beginTag('td');
		$html[] = strtoupper($this->code);
		$html[] = Html::endTag('td');
		$html[] = Html::endTag('tr');
		$html[] = Html::endTag('table');
		return implode("\n", $html);
	}

	/**
	 * 渲染区块
	 * @param string $decode
	 * @throws Exception
	 */
	protected function renderCode($decode) {
		if (strlen($decode) != 9) {
			throw new Exception('一维码编码失败');exit;
		}
		$html = [];
		for ($i=0;$i<9;$i++) {
			$patten = $this->getPatten($decode[$i]);
			$html[] = $this->renderItem($patten['color'], $patten['width']);
			$html[] = Html::endTag('td');
		}
		return implode("\n", $html);
	}

	/**
	 * 渲染字符
	 * @param string $bgcolor
	 * @param string $width
	 */
	protected function renderItem($bgcolor, $width) {
		$html = [];
		$html[] = Html::beginTag('td', [
			'height'	=> $this->height,
			'bgcolor'	=> $bgcolor,
			'width'		=> $width,
		]);
		$html[] = Html::endTag('td');
		return implode("\n", $html);
	}

	protected function decode($code) {
		$code = strtoupper($code);
		$decode = "212030302";
		switch ($code)
		{
			case "0":$decode = ("202130302");break;
			case "1":$decode = ("302120203");break;
			case "2":$decode = ("203120203");break;
			case "3":$decode = ("303120202");break;
			case "4":$decode = ("202130203");break;
			case "5":$decode = ("302130202");break;
			case "6":$decode = ("203130202");break;
			case "7":$decode = ("202120303");break;
			case "8":$decode = ("302120302");break;
			case "9":$decode = ("203120302");break;
			case "A":$decode = ("302021203");break;
			case "B":$decode = ("203021203");break;
			case "C":$decode = ("303021202");break;
			case "D":$decode = ("202031203");break;
			case "E":$decode = ("302031202");break;
			case "F":$decode = ("203031202");break;
			case "G":$decode = ("202021303");break;
			case "H":$decode = ("302021302");break;
			case "I":$decode = ("203021300");break;
			case "J":$decode = ("202031302");break;
			case "K":$decode = ("302020213");break;
			case "L":$decode = ("203020213");break;
			case "M":$decode = ("303020212");break;
			case "N":$decode = ("202030213");break;
			case "O":$decode = ("302030212");break;
			case "P":$decode = ("203030212");break;
			case "Q":$decode = ("202020313");break;
			case "R":$decode = ("302020312");break;
			case "S":$decode = ("203020312");break;
			case "T":$decode = ("202030312");break;
			case "U":$decode = ("312020203");break;
			case "V":$decode = ("213020203");break;
			case "W":$decode = ("313020202");break;
			case "X":$decode = ("212030203");break;
			case "Y":$decode = ("312030202");break;
			case "Z":$decode = ("213030202");break;
			case "-":$decode = ("212020303");break;
			case ".":$decode = ("312020302");break;
			case " ":$decode = ("213020302");break;
			case "*":$decode = ("212030302");break;
			case "$":$decode = ("212121202");break;
			case "/":$decode = ("212120212");break;
			case "+":$decode = ("212021212");break;
			case "%":$decode = ("202121212");break;
			default: break;
		}
		return $decode;
	}

	/**
	 * Code39编码宽窄比3：1， $index取值0,1,2,3
	 * @param integer $index
	 */
	protected function getPatten($index) {
		if (!in_array($index, [0,1,2,3])) {
			throw new Exception('一维码编码失败');exit;
		}
		$pattenMap = [
			['color' => '#FFFFFF', 'width' => 1*$this->zoom],
			['color' => '#FFFFFF', 'width' => 3*$this->zoom],
			['color' => '#000000', 'width' => 1*$this->zoom],
			['color' => '#000000', 'width' => 3*$this->zoom],
		];
		return $pattenMap[$index];
	}
}
