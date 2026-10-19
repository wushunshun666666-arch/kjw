<?php
/**
 * Html.php
 *
 * @author suxh
 */

namespace app\helpers;

use yii\web\Request;
use common\widgets\Code39Widget;

class Html extends \yii\helpers\Html
{
	public static function checkbox($name, $checked = false, $options = []) {
		return static::bootstrapBooleanInput('checkbox', $name, $checked, $options);
	}

	public static function radio($name, $checked = false, $options = []) {
		return static::bootstrapBooleanInput('radio', $name, $checked, $options);
	}

	public static function readonly($name, $value = null, $options = []) {
		$options['readonly'] = 'readonly';
		self::addCssClass($options, 'form-control');
        return parent::textInput($name, $value, $options);
    }

	public static function readonlyTextarea($name, $value = '', $options = []) {
		$options['readonly'] = 'readonly';
		self::addCssClass($options, 'form-control');
		return parent::textarea($name, $value, $options);
	}

	/**
	 * 导航链接(主窗口导航)
	 */
	public static function navigate($label, $url, $options = []) {
		$route = $url[0];
		$url = Url::to($url);
		$url = Json::htmlEncode([$route, $url]);

		$onclick = "seajs.use('admin.ui', function(ui) {ui.navigate($url);});";
		$options['onclick'] = $onclick;

		return self::a($label, 'javascript:void(0);', $options);
	}

	/**
	 * 导航链接(当前窗口导航)
	 */
	public static function navigateA($label, $url, $options = []) {
		$route = $url[0];
		$url = Url::to($url);
		$url = Json::htmlEncode([$route, $url]);

		$onclick = "seajs.use('admin.html', function(html) {html.navigateA($url);});";
		$options['onclick'] = $onclick;

		return self::a($label, 'javascript:void(0);', $options);
	}

	/**
	 * 导航按钮(当前窗口导航)
	 */
	public static function navigateButton($content, $url, $options = []) {
		$route = $url[0];
		$url = Url::to($url);
		$url = Json::htmlEncode([$route, $url]);

		$onclick = "seajs.use('admin.html', function(html) {html.navigateA($url);});";
		$options['onclick'] = $onclick;

		return self::button($content, $options);
	}

	/**
	 * 确认链接
	 */
	public static function confirmA($label, $url, $message, $options = []) {
		$route = $url[0];
		$url = Url::to($url);
		$url = Json::htmlEncode([$route, $url]);
		$message = Json::htmlEncode($message);
		if (isset($options['success'])) {
			$success = $options['success'];
			unset($options['success']);
		}
		else {
			$success = null;
		}
		$success = Json::htmlEncode($success);

		$onclick = "seajs.use(['admin.html'], function(html) {html.confirmA({$message}, {$url}, {$success}); });";
		$options['onclick'] = $onclick;

		return self::a($label, 'javascript:void(0);', $options);
	}

	/**
	 * 弹窗链接
	 */
	public static function modalA($label, $url, $options = []) {
		$options = self::modalOptions($options, $url, $label);

		return self::a($label, 'javascript:void(0);', $options);
	}

	/**
	 * 导出Excel按钮
	 */
	public static function exporterA($label, $url, $options = []) {
		if (!isset($options['id'])) {
			$options['id'] = 'exporter-a';
		}

		$route = $url[0];
		$url = Url::to($url);

		$route = Json::htmlEncode($route);
		$url = Json::htmlEncode($url . '?=') . '+$(\'#'. $options['id'] .'\').closest("form").serialize()';

		$onclick = "seajs.use('admin.ui', function(ui) {ui.navigate([{$route}, {$url}])});";
		$options['onclick'] = $onclick;

		return self::a($label, 'javascript:void(0);', $options);
	}

	/**
	 * 弹窗按钮
	 */
	public static function modalButton($content, $url, $options = []) {
		$options = self::modalOptions($options, $url, $content);

		return self::button($content, $options);
	}

	/**
	 * 返回按钮
	 */
	public static function backButton($options=[], $content='返回') {
		if (!isset($options['class'])) {
			$options['class'] = 'btn btn-light border shadow-sm px-3 py-2';
		}
		if (!isset($options['onclick'])) {
			if (isset($options['backurl'])) {
				$options['onclick'] = 'window.location.href="'.$options['backurl'].'";';
				unset($options['backurl']);
			}
			else {
				$options['onclick'] = "window.history.back();";
			}
		}
		if (!isset($options['title'])) {
			$options['title'] = '点击返回';
		}

		return static::button($content, $options);
	}

	/**
	 * 取消按钮
	 */
	public static function cancelButton($options=[], $content='取消') {
		if (!isset($options['class'])) {
			$options['class'] = 'btn btn-light';
		}
		if (!isset($options['title'])) {
			$options['title'] = '点击取消';
		}
		if (!isset($options['onclick'])) {
			$options['onclick'] = "seajs.use('admin.ui', function(ui) {ui.closeModal();});";
		}

		return static::button($content, $options);
	}

	/**
	 * 确定按钮
	 */
	public static function okButton($options=[], $content='确定') {
		if (!isset($options['class'])) {
			$options['class'] = 'btn btn-primary';
		}
		if (!isset($options['onclick'])) {
			$options['onclick'] = "seajs.use('admin.ui', function(ui) {ui.closeModal();});";
		}

		return static::button($content, $options);
	}

	public static function resourceImg($src, $options = []) {
		// 图片路径为空时，使用的默认图片
		$emptyImg = self::getDefaultReourceImg($options, 'empty');
		// 图片路径有错时，使用的默认图片
		$errorImg = self::getDefaultReourceImg($options, 'error');

		if (empty($src)) {
			$src = $emptyImg;
		}

		$src = \Yii::$app->resource->getViewUrl($src);
		if ($src === false) {
			$src = \Yii::$app->resource->getViewUrl($errorImg);
		}

		return self::a(parent::img($src, $options), $src, [
			'target' => '_blank',
			'title' => '点击查看大图',
		]);
    }

    public static function activeQueryInput($model, $attribute, $options=[]) {
    	if (isset($options['id'])) {
    		$id = $options['id'];
    		unset($options['id']);
    	}
    	else {
    		$id = self::getInputId($model, $attribute);
    	}
    	$containerId = $id.'-wrap';

    	if (!isset($options['disabled'])) {
    		$options['readonly'] = 'readonly';
    	}

    	$url = self::getOption($options, 'url');
    	$url = Url::to($url);

    	$clientOptions = [];
    	$clientOptions['modalTitle'] = self::getOption($options, 'title', '查询');
    	$clientOptions['modalWidth'] = self::getOption($options, 'modalWidth');
    	$clientOptions['modalHeight'] = self::getOption($options, 'modalHeight');
    	$clientOptions['modalPost']  = self::getOption($options, 'modalPost');
    	$clientOptions['onSelect'] 	 = self::getOption($options, 'onSelect');

    	if ($clientOptions['modalPost']) {
    		$request = \Yii::$app->getRequest();
    		if ($request instanceof Request && $request->enableCsrfValidation) {
    			$clientOptions['csrf'] = [
    				'param'=>$request->csrfParam,
    				'token'=>$request->getCsrfToken()
    			];
     		}
    	}

    	$url = Json::htmlEncode($url);
    	$clientOptions = Json::htmlEncode($clientOptions);

    	$js = "seajs.use(['admin.html'], function() {\$('#{$containerId}').queryInput({$url}, {$clientOptions}); });";
    	$view = \Yii::$app->getView();
    	$view->registerJs($js);

    	$text = self::getOption($options, 'text', '');
    	$input = '';
    	$input .= Html::beginTag('div', ['class'=>'input-group input-query', 'id'=>$containerId]);
    	$input .= Html::activeHiddenInput($model, $attribute, ['id'=>$id]);
    	$input .= Html::textInput('', $text, $options);
     $input .= Html::beginTag('div', ['class'=>'input-group-text']);
    	$input .= Html::tag('i', '', ['class'=>'fa fa-search']);
    	$input .= Html::endTag('div');
    	$input .= Html::endTag('div');

    	return $input;
    }

    /**
     * 下拉框选项
     * @param array $items
     * @param array $options
     * @return string
     */
    public static function selectOptions($items, $options=[]) {
        $value = (isset($options['emptyValue']))?$options['emptyValue']:'';
        $name = (isset($options['emptyName']))?$options['emptyName']:'请选择';
		$tagOptions = [
			'prompt' => [
				'text' => $name,
				'options' => ['value' => $value],
			],
			'encode' => $options['encode'] ?? true,
		];
		return static::renderSelectOptions(null, $items, $tagOptions);
    }

	/**
	 * 一维码(code39)
	 * @param string $code
	 */
	public static function code39($code, $options=[]) {
		return Code39Widget::widget(array_merge($options, [
			'code' => $code,
		]));
	}

	public static function toDropDownListAjaxOptions($items) {
		$options = [];
		foreach ($items as $key=>$value) {
			$options[] = [
				'id' => $key,
				'name' => $value,
			];
		}

		return $options;
	}

	public static function setDropDownListAjaxOptions($options, $emptyValue, $emptyName) {
		if (isset($options['ajax'])) {
			$ajax = $options['ajax'];
			unset($options['ajax']);

			$url = (is_array($ajax['url']))? Url::to($ajax['url']) : $ajax['url'];
			$url = Json::htmlEncode($url);
			$target = Json::htmlEncode($ajax['update']);

			$clientOptions = [];
			$clientOptions['name'] = $ajax['name'];
			$clientOptions['emptyValue'] = $emptyValue;
			$clientOptions['emptyName'] = $emptyName;
			$clientOptions = Json::htmlEncode($clientOptions);

			$onchange = "var source=this;seajs.use(['admin.html'], function(html) {html.ajaxUpdateDropDownList(source, {$url}, {$target}, {$clientOptions}); });";
			$options['onchange'] = $onchange;
		}

		return $options;
	}

	public static function contrastDigital($old, $new) {
		if (abs($old - $new) > 0.001) {
			$old = self::tag('span', $old, ['class'=>'red-line']);
		}
		return $old.'/'.$new;
	}

	private static function getDefaultReourceImg(&$options, $type) {
		if (isset($options[$type])) {
			$img = $options[$type];
			unset($options[$type]);
		}
		else if (isset($options['default'])) {
			$img = $options['default'];
			unset($options['default']);
		}
		else {
			$img = "@public/images/{$type}.jpg";
		}

		return $img;
    }

	private static function bootstrapBooleanInput($type, $name, $checked, $options) {
		self::addCssClass($options, 'form-check-input');
		if (!array_key_exists('label', $options)) {
			return $type === 'radio'
				? parent::radio($name, $checked, $options)
				: parent::checkbox($name, $checked, $options);
		}

		$label = $options['label'];
		$labelOptions = $options['labelOptions'] ?? [];
		$containerOptions = $options['containerOptions'] ?? [];
		$containerTag = $options['containerTag'] ?? 'div';
		unset($options['label'], $options['labelOptions'], $options['containerOptions'], $options['containerTag']);

		self::addCssClass($labelOptions, 'form-check-label');
		self::addCssClass($containerOptions, 'form-check form-check-inline');
		if (!isset($options['id'])) {
			static $counter = 0;
			$baseId = static::getInputIdByName($name);
			$options['id'] = ($baseId !== '' ? $baseId : $type) . '-' . ++$counter;
		}

		$input = $type === 'radio'
			? parent::radio($name, $checked, $options)
			: parent::checkbox($name, $checked, $options);
		$labelHtml = static::label($label, $options['id'], $labelOptions);

		return static::tag($containerTag, $input . $labelHtml, $containerOptions);
	}

	private static function modalOptions($options, $url, $title) {
	    if (is_array($url)) {
	        $route = $url[0];
	        $url = Url::to($url);
	        $url = [$route, $url];
	    }
	    $url = Json::htmlEncode($url);
	    
	    $width = self::getOption($options, 'modalWidth');
	    $height = self::getOption($options, 'modalHeight');
	    
	    if (!isset($options['title'])) {
	        $options['title'] = $title;
	    }
	    $title = Json::htmlEncode($options['title']);
	    
	    $modalOptions = [];
	    if (isset($options['modalPost'])) {
	        $modalOptions['post'] = $options['modalPost'];
	        unset($options['modalPost']);
	        
	        $request = \Yii::$app->getRequest();
	        if ($request instanceof Request) {
	            if ($request->enableCsrfValidation) {
	                $modalOptions['csrf'] = [
	                    'param'=>$request->csrfParam,
	                    'token'=>$request->getCsrfToken()
	                ];
	            }
	        }
	    }
	    if (isset($options['modalCallback'])) {
	        $modalOptions['callback'] = $options['modalCallback'];
	        unset($options['modalCallback']);
	    }
	    $modalOptions = Json::htmlEncode($modalOptions);
	    
	    // ✅关键修复：数字/null全部通过Json::htmlEncode输出，null会输出JS的null，不会变成空字符串！
	    $widthJs = Json::htmlEncode($width);
	    $heightJs = Json::htmlEncode($height);
	    
	    $onclick = "seajs.use(['admin.html'], function(html) {html.modalA({$url},{$title},{$widthJs},{$heightJs},{$modalOptions}); });";
	    $options['onclick'] = $onclick;
	    
	    return $options;
	}

	private static function getOption(&$options, $name, $default=null, $unset=true) {
		if (isset($options[$name])) {
			$value = $options[$name];
			unset($options[$name]);
		}
		else {
			$value = $default;
		}

		return $value;
	}
}
