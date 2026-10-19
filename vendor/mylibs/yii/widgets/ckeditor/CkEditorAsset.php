<?php
/**
 * CkEditorAsset.php
 * @author suxh
 */

namespace mylibs\yii\widgets\ckeditor;

use yii\web\AssetBundle;

class CkEditorAsset extends AssetBundle
{
	public $sourcePath = '@mylibs/yii/widgets/ckeditor/assets';
	public $js = [
		'ckeditor.js',
	];
}