<?php
/**
 * AdminLteAsset.php
 * @author suxh
 */

namespace mylibs\yii\ui\adminLte;

use yii\web\AssetBundle;

class AdminLteAsset extends AssetBundle
{
	// AdminLTE 4.9.1 with the legacy visual theme required by this project.
	// Components run on AdminLTE 4 / Bootstrap 5 while retaining the old UI.
	public $sourcePath = '@mylibs/yii/ui/adminLte/assets';
	public $js = [
		'bootstrap/js/bootstrap.bundle.min.js',
		'dist/js/app.min.js',
		'dist/js/chart.umd.min.js',
	];
	public $css = [
        'bootstrap/css/bootstrap.min.css',
        'font-awesome/css/font-awesome.min.css',
        'dist/css/AdminLTE.min.css',
		'theme/classic.css',
    ];
	public $depends = [
		'yii\web\JqueryAsset',
	];
}
