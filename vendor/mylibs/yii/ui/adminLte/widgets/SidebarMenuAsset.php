<?php
/**
 * SidebarMenuAsset.php
 * @author suxh
 */

namespace mylibs\yii\ui\adminLte\widgets;

use yii\web\AssetBundle;

class SidebarMenuAsset extends AssetBundle
{
	public $sourcePath = '@mylibs/ui/adminLte/assets';
	public $js = [
		//'dist/js/app.min.js',
	];
	public $depends = [
		'mylibs\ui\adminLte\AdminLteAsset',
	];
}