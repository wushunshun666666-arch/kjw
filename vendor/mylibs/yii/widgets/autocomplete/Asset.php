<?php
/**
 * Asset.php
 * @author suxh
 */

namespace mylibs\yii\widgets\autocomplete;

use yii\web\AssetBundle;

class Asset extends AssetBundle
{
	public $sourcePath = '@mylibs/yii/widgets/autocomplete/jquery-ui';
	public $js = [
		'jquery-ui.min.js',
	];
	public $css = [
		'jquery-ui.min.css',
	];
}