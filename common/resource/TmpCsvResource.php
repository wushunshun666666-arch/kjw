<?php
/**
 * CSV临时资源
 * @author suxh
 */

namespace common\resource;

class TmpCsvResource extends Resource
{
	public $extensions = ['csv', 'txt'];
	public $maxSize = 2*1024*1024;
	public $appId = 'tmp-csv';

	public function getUploadUrl() {
		return \Yii::$app->resource->getTmpUploadUrl();
	}

	public function getUploadParams() {
		return \Yii::$app->resource->createUploadParams($this->appId);
	}

	public function delete($tmpFile) {
		$resource = \Yii::$app->resource;
		if (!$resource->delete($tmpFile)) {
			return $this->setError($resource->getError());
		}

		return true;
	}

	public function getContents($tmpFile) {
		$resource = \Yii::$app->resource;
		$contents = $resource->getContents($tmpFile);
		if ($contents === false) {
			return $this->setError($resource->getError());
		}

		return $contents;
	}
}