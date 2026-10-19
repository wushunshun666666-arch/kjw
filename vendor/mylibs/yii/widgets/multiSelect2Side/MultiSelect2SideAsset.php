<?php
/**
 * MultiSelect2SideAsset.php
 * @author suxh
 */

namespace mylibs\yii\widgets\multiSelect2Side;

use yii\web\AssetBundle;

class MultiSelect2SideAsset extends AssetBundle
{
	public $sourcePath = '@mylibs/yii/widgets/multiSelect2Side/assets';
	public $js = [
		'MultiSelect2Side.js',
	];
	public $css = [
		'MultiSelect2Side.css',
	];
}