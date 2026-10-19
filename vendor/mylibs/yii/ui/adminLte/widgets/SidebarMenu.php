<?php
/**
 * SidebarMenu.php
 * @author suxh
 */

namespace mylibs\yii\ui\adminLte\widgets;

use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Html;

class SidebarMenu extends Widget
{
	public $items = [];
	public $options=['class'=>'nav sidebar-menu flex-column', 'data-lte-toggle'=>'treeview', 'role'=>'navigation'];
	public $itemOptions = [];
	public $route;
	public $params;
	public $activeCssClass = 'active';
	public $firstItemCssClass;
	public $lastItemCssClass;
	public $linkTemplate = '<a class="nav-link" href="{url}"><i class="nav-icon {i}"></i><p>{label}{right-i}</p></a>';
	public $labelTemplate = '<a class="nav-link" href="#"><i class="nav-icon {i}"></i><p>{label}{right-i}</p></a>';
	public $submenuTemplate = "\n<ul class='nav nav-treeview'>\n{items}\n</ul>\n";
	public $encodeLabels = true;
	public $hideEmptyItems = true;
	public $activateItems = true;
	public $activateParents = true;
	
	public function init() {
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}
	}

	public function run() {
		if ($this->route === null && Yii::$app->controller !== null) {
			$this->route = Yii::$app->controller->getUniqueId();
		}
		if ($this->params === null) {
			$this->params = Yii::$app->request->getQueryParams();
		}

		$items = $this->normalizeItems($this->items, $hasActiveChild);

		if (!empty($items)) {
			$options = $this->options;
			$tag = ArrayHelper::remove($options, 'tag', 'ul');

			if ($tag !== false) {
				echo Html::tag($tag, $this->renderItems($items), $options);
			} else {
				echo $this->renderItems($items);
			}
		}
		
		//$view = $this->getView();
		//SidebarMenuAsset::register($view);
		//$id = $this->options['id'];
		//$view->registerJs("$.AdminLTE.tree('#$id');");
	}

	protected function renderItems($items) {
		$n = count($items);
		$lines = [];
		foreach ($items as $i => $item) {
			$options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));
			$tag = ArrayHelper::remove($options, 'tag', 'li');
			$class = [];
			if ($item['active'] && empty($item['items'])) {
				$class[] = $this->activeCssClass;
			}
			if ($i === 0 && $this->firstItemCssClass !== null) {
				$class[] = $this->firstItemCssClass;
			}
			if ($i === $n - 1 && $this->lastItemCssClass !== null) {
				$class[] = $this->lastItemCssClass;
			}
			if (!empty($item['items'])) {
				$class[] = 'nav-item';
				if ($item['active']) {
					$class[] = 'menu-open';
				}
			} elseif (!in_array('nav-item', $class, true)) {
				$class[] = 'nav-item';
			}

			if (!empty($class)) {
				if (empty($options['class'])) {
					$options['class'] = implode(' ', $class);
				} else {
					$options['class'] .= ' ' . implode(' ', $class);
				}
			}

			$menu = $this->renderItem($item);
			if (!empty($item['items'])) {
				$submenuTemplate = ArrayHelper::getValue($item, 'submenuTemplate', $this->submenuTemplate);
				$menu .= strtr($submenuTemplate, [
						'{items}' => $this->renderItems($item['items']),
						]);
			}
			if ($tag === false) {
				$lines[] = $menu;
			} else {
				$lines[] = Html::tag($tag, $menu, $options);
			}
		}

		return implode("\n", $lines);
	}

	protected function renderItem($item) {
		if (!empty($item['items'])) {
			$item['right-i'] = '<i class="nav-arrow fa fa-angle-right"></i>';
		}
		else {
			$item['right-i'] = '';
		}
		 
		if (isset($item['url'])) {
			$template = ArrayHelper::getValue($item, 'template', $this->linkTemplate);

			return strtr($template, [
					'{url}' => Html::encode(Url::to($item['url'])),
					'{label}' => $item['label'],
					'{i}' => $item['i'],
					'{right-i}' => $item['right-i'],
					]);
		} else {
			$template = ArrayHelper::getValue($item, 'template', $this->labelTemplate);

			return strtr($template, [
					'{label}' => $item['label'],
					'{i}' => $item['i'],
					'{right-i}' => $item['right-i'],
					]);
		}
	}

	protected function normalizeItems($items, &$active) {
		foreach ($items as $i => $item) {
			if (isset($item['visible']) && !$item['visible']) {
				unset($items[$i]);
				continue;
			}
			if (!isset($item['label'])) {
				$item['label'] = '';
			}
			if (!isset($item['i'])) {
				$items[$i]['i'] = 'fa fa-circle-o';
			}

			$encodeLabel = isset($item['encode']) ? $item['encode'] : $this->encodeLabels;
			$items[$i]['label'] = $encodeLabel ? Html::encode($item['label']) : $item['label'];
			$hasActiveChild = false;
			if (isset($item['items'])) {
				$items[$i]['items'] = $this->normalizeItems($item['items'], $hasActiveChild);
				if (empty($items[$i]['items']) && $this->hideEmptyItems) {
					unset($items[$i]['items']);
					if (!isset($item['url'])) {
						unset($items[$i]);
						continue;
					}
				}
			}
			if (!isset($item['active'])) {
				if ($this->activateParents && $hasActiveChild || $this->activateItems && $this->isItemActive($item)) {
					$active = $items[$i]['active'] = true;
				} else {
					$items[$i]['active'] = false;
				}
			} elseif ($item['active']) {
				$active = true;
			}
		}

		return array_values($items);
	}

	protected function isItemActive($item) {
		if (isset($item['url']) && is_array($item['url']) && isset($item['url'][0])) {
			$route = $item['url'][0];
			if ($route[0] !== '/' && Yii::$app->controller) {
				$route = Yii::$app->controller->module->getUniqueId() . '/' . $route;
			}
			
			$pattern = '`'.$this->route.'`i';
			if (!preg_match($pattern, ltrim($route, '/'))) {
				return false;
			}
			unset($item['url']['#']);
			if (count($item['url']) > 1) {
				$params = $item['url'];
				unset($params[0]);
				foreach ($params as $name => $value) {
					if ($value !== null && (!isset($this->params[$name]) || $this->params[$name] != $value)) {
						return false;
					}
				}
			}

			return true;
		}

		return false;
	}
}
