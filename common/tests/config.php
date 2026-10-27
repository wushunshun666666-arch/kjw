<?php

/** Codeception 始终读取测试环境，不借用开发或生产数据库。 */
$root = dirname(__DIR__, 2);
if (defined('YII_ENV') && YII_ENV !== 'test') {
    throw new RuntimeException('测试必须使用 YII_ENV=test。');
}
defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', false);
$environmentConfig = require $root . '/common/config/main-test.php';
$consoleConfig = $environmentConfig['applications']['console'] ?? [];
unset($environmentConfig['applications']);
$config = yii\helpers\ArrayHelper::merge(
    require $root . '/common/config/main.php',
    require $root . '/console/config/main.php',
    $environmentConfig,
    $consoleConfig
);
$config['id'] = 'app-common-tests';
$config['basePath'] = dirname(__DIR__);
return $config;
