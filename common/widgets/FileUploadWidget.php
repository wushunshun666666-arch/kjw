<?php
/**
 * FileUploadWidget.php
 * @author suxh
 */

namespace common\widgets;

use yii\base\Widget;
use yii\helpers\Json;
use yii\helpers\Html;

class FileUploadWidget extends Widget
{
	/**
	 * @var string 文件上传路径
	 */
	public $action = null;
	/**
	 * @var array 文件类型，如['png','jpg']
	 */
	public $extensions = [];
	/**
	 * @var integer 文件大小，默认为1MB
	 */
	public $maxSize = 1024*1024;
	public $extensionsMessage = null;
	public $maxSizeMessage = null;
	public $clientOptions = [];
	public $fileInputName = 'upload';
	public $fileInputOptions = [];
	public $hiddenInputs = [];

	public $tag = 'div';
	public $options = ['class' => 'file-upload'];
	public $buttonTag = 'label';
	public $buttonContent = '上传';
	public $buttonOptions = ['class'=>'btn btn-light'];
	public $helpBlockTag = 'span';
	public $helpBlockOptions = ['class'=>'form-text'];

	/**
	 * @inheritdoc
	 */
	public function run() {
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}

		$this->renderContent();

		$this->registerClientScript();
	}

	protected function renderContent() {
		$html = [];
		$html[] = Html::beginTag($this->tag, $this->options);
		$html[] = Html::tag($this->buttonTag, $this->buttonContent, $this->buttonOptions);
		$html[] = Html::tag($this->helpBlockTag, '', $this->helpBlockOptions);
		$html[] = Html::endTag($this->tag);

		echo implode("\n", $html);
	}

	/**
	 * 注册客户端脚本(js或css样式表)
	 */
	protected function registerClientScript() {
		// 注册JS脚本
		$options = $this->clientOptions;
		$options['buttonTag']		= $this->buttonTag;
		$options['action']	 		= $this->action;
		$options['fileInputName']	= $this->fileInputName;
		$options['hiddenInputs']	= $this->hiddenInputs;
		$options['extensions'] 		= $this->extensions;
		$options['maxSize'] 		= $this->maxSize;
		$options['extensionsMessage'] = $this->extensionsMessage;
		$options['maxSizeMessage'] 	= $this->maxSizeMessage;

		$options = Json::htmlEncode($options);
		$id = $this->options['id'];
		$js = "seajs.use('wgt.upload', function(upload) {\$('#$id').upload($options);});";

		$view = $this->getView();
		$view->registerJs($js);
	}
}
