<?php

/** 后台集成测试引导，不使用真实浏览器登录态。 */
define('YII_DEBUG', false);
define('YII_ENV', 'test');
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
$config = require $root . '/common/config/main-dev.php';
$common = require $root . '/common/config/main.php';
new yii\web\Application([
    'id' => 'portal-admin-tests', 'basePath' => $root . '/admin', 'controllerNamespace' => 'app\controllers',
    'vendorPath' => $root . '/vendor', 'aliases' => $common['aliases'],
    'components' => [
        'assetManager' => $common['components']['assetManager'],
        'db' => $config['components']['db'], 'cache' => ['class' => yii\caching\DummyCache::class],
        'request' => ['cookieValidationKey' => 'isolated-test-key', 'scriptFile' => $root . '/admin/web/index.php', 'scriptUrl' => '/index.php', 'baseUrl' => '', 'url' => '/index.php', 'hostInfo' => 'http://localhost'],
        'user' => ['identityClass' => common\models\system\UserIdentity::class, 'enableSession' => false],
    ],
    'params' => ['assetVersion' => 'portal-tests'],
]);
