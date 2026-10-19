<?php
/**
 * 浏览器升级提示
 * @author suxh
 */

namespace mylibs\yii\ui\adminLte\widgets;

use yii\base\Widget;
use yii\helpers\Html;

class BrowserUpgradeTips extends Widget
{
	/**
	 * @var array 外层元素html属性
	 */
	public $options = ['class'=>'alert alert-warning alert-dismissible browser-upgrade-tips'];

	public function run() {
		if ($this->isBrowserNeedUpgrade()) {
			echo Html::beginTag('div', $this->options);
			echo '<button class="btn-close" aria-label="关闭" data-bs-dismiss="alert" type="button"></button>';
			echo Html::tag('h4', '您的浏览器版本过低');
			echo Html::tag('p', '系统检测到您正在使用低版本的IE浏览器(或以IE为内核的浏览器)。');
			echo Html::tag('p', '本系统支持谷歌、火狐或IE9、IE10、IE11浏览器。为确保系统能够正常使用，建议您升级IE浏览器，或使用谷歌、火狐浏览器。');
			echo Html::tag('p', '如果使用360浏览器，请使用极速模式。');
			echo Html::tag('p', '<a href="http://www.firefox.com.cn/download/" target="_blank">下载火狐浏览器</a>');
			echo Html::endTag('div');
		}
	}

	protected function isBrowserNeedUpgrade() {
		$browser = $this->getBrowser();
		return (in_array($browser, ['IE6.0', 'IE7.0', 'IE8.0']));
	}

	protected function getBrowser() {
		$browser = '';
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$userAgent = $_SERVER['HTTP_USER_AGENT'];
			if (strpos($userAgent, 'MSIE 6.0')!==false) {
				$browser = 'IE6.0';
			}
			else if (strpos($userAgent, 'MSIE 7.0')!==false) {
				$browser = 'IE7.0';
			}
			else if (strpos($userAgent, 'MSIE 8.0')!==false) {
				$browser = 'IE8.0';
			}
		}

		return $browser;
	}
}
