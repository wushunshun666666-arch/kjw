<?php

namespace frontend\assets;

/** 多彩种界面的独立模块，保持原站基础字体、颜色与资源。 */
final class GamePortalAsset extends \yii\web\AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['assets1/index-DqNYD7AL.css', 'css/portal-games.css'];
    public $js = [['js/portal-games.js', 'type' => 'module']];
}
