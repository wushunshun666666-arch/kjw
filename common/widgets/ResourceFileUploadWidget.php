<?php
/**
 * ResourceFileUploadWidget.php
 * @author suxh
 */

namespace common\widgets;

use yii\base\Exception;

class ResourceFileUploadWidget extends FileUploadWidget
{
	public $resource = null;

	public function init() {
		if ($this->resource == null) {
			throw new Exception('$resource对象必须指定');
		}

		$resource = $this->resource;
		$this->extensions = $resource->extensions;
		$this->maxSize = $resource->maxSize;
		$this->action = $resource->getUploadUrl();
		$this->hiddenInputs = $resource->getUploadParams();

		parent::init();
	}
}