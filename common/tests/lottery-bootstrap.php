<?php

/** 只读加载当前实例配置，测试写入由每例事务回滚。 */
define('YII_DEBUG', false);
define('YII_ENV', 'test');
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/common/config/bootstrap.php';
Yii::setAlias('@frontend', $root . '/frontend');
$config = require $root . '/common/config/main-dev.php';
new yii\console\Application([
    'id' => 'portal-tests', 'basePath' => $root . '/console', 'timeZone' => 'Asia/Shanghai',
    'components' => ['db' => $config['components']['db'], 'cache' => ['class' => yii\caching\DummyCache::class]],
]);
common\models\LogicModel::setIdentity(new common\models\SystemUserIdentity());
