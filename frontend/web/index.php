<?php

/** 公共网站入口；默认关闭调试，数据库沿用当前环境配置。 */
defined('YII_DEBUG') or define('YII_DEBUG', false);
$environment = getenv('YII_ENV') ?: 'dev';
if (!in_array($environment, ['dev', 'test', 'prod'], true)) { $environment = 'prod'; }
defined('YII_ENV') or define('YII_ENV', $environment);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/vendor/yiisoft/yii2/Yii.php';
require dirname(__DIR__, 2) . '/common/config/bootstrap.php';
Yii::setAlias('@frontend', dirname(__DIR__));
$shared = require dirname(__DIR__, 2) . '/common/config/main-' . YII_ENV . '.php';
$config = require dirname(__DIR__) . '/config/main.php';
$config['vendorPath'] = dirname(__DIR__, 2) . '/vendor';
$config['components']['db'] = $shared['components']['db'];
(new yii\web\Application($config))->run();
