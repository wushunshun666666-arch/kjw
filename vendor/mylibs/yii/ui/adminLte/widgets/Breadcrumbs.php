<?php
/**
 * Breadcrumbs.php
 * @author suxh
 */

namespace mylibs\yii\ui\adminLte\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class Breadcrumbs extends Widget
{
	public $tag = 'ol';
	public $options = ['class' => 'breadcrumb'];
	public $encodeLabels = true;
	public $homeLink;
	public $links = [];
	public $homeItemTemplate = "<li class='breadcrumb-item'><i class='fa fa-home'></i>&nbsp;{link}</li>\n";
	public $itemTemplate = "<li class='breadcrumb-item'>{link}</li>\n";
	public $activeItemTemplate = "<li class=\"breadcrumb-item active\" aria-current=\"page\">{link}</li>\n";

	/**
	 * (non-PHPdoc)
	 * @see \yii\base\Widget::run()
	 */
	public function run() {
		if (empty($this->links)) {
			return;
		}
		$links = [];
		if ($this->homeLink === null) {
			$links[] = $this->renderItem([
					'label' => Yii::t('yii', 'Home'),
					'url' => Yii::$app->homeUrl,
					], $this->homeItemTemplate);
		} elseif ($this->homeLink !== false) {
			$links[] = $this->renderItem($this->homeLink, $this->homeItemTemplate);
		}
		foreach ($this->links as $link) {
			if (!is_array($link)) {
				$link = ['label' => $link];
			}
			$links[] = $this->renderItem($link, isset($link['url']) ? $this->itemTemplate : $this->activeItemTemplate);
		}
		echo Html::tag($this->tag, implode('', $links), $this->options);
	}

	protected function renderItem($link, $template) {
		$encodeLabel = $encode = ArrayHelper::remove($link, 'encode', $this->encodeLabels);
		if (array_key_exists('label', $link)) {
			$label = $encodeLabel ? Html::encode($link['label']) : $link['label'];
		} else {
			throw new InvalidConfigException('The "label" element is required for each link.');
		}
		if (isset($link['template'])) {
			$template = $link['template'];
		}
		if (isset($link['url'])) {
			$options = $link;
			unset($options['template'], $options['label'], $options['url']);
			$link = Html::a($label, $link['url'], $options);
		} else {
			$link = $label;
		}
		return strtr($template, ['{link}' => $link]);
	}
}
