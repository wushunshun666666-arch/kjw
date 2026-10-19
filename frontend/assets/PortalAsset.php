<?php

namespace frontend\assets;

/** 固定版本的参考前端，不加载后台主题。 */
final class PortalAsset extends \yii\web\AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = ['assets1/index-DqNYD7AL.css', 'css/portal-games.css?v=20261019-7'];
    public $js = [['assets1/index-zEjWpYFa.js?v=20261019-7', 'type' => 'module']];
}
