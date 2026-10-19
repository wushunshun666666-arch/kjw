<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [
    	'js/sea.js',
		'js/bootstrap-components.js',
    ];
    public $jsOptions = [
		'position'=>View::POS_HEAD,
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'mylibs\yii\ui\adminLte\AdminLteAsset',
    ];

    public function registerAssetFiles($view) {
    	$version = \Yii::$app->params['assetVersion'];
        $initjs = <<< JS
seajs.config({
	'map' : [ [ /^(.*\.(?:css|js))(.*)$/i, '$1?{$version}' ] ]
});
JS;
    	$view->registerJs($initjs, View::POS_HEAD);
        parent::registerAssetFiles($view);
    }
}
