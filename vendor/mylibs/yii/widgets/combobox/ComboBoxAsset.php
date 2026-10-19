<?php
/**
 * ComboBoxAsset.php
 * @author suxh
 */

namespace mylibs\yii\widgets\combobox;

use yii\web\AssetBundle;

class ComboBoxAsset extends AssetBundle
{
	public $sourcePath = '@mylibs/yii/widgets/combobox/selectize.js/dist';
	public $js = [
		'js/selectize.js',
	];
	public $css = [
		'css/selectize.bootstrap5.css',
		'css/selectize.admin.css',
	];
}
