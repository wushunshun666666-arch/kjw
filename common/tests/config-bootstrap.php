<?php

/** 配置单元测试只创建最小应用，不载入任何站点凭据或连接业务数据库。 */
define('YII_ENV', 'test');
define('YII_DEBUG', false);
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
new yii\console\Application([
    'id' => 'configuration-tests',
    'basePath' => $root . '/common',
    'components' => ['cache' => yii\caching\DummyCache::class],
]);
