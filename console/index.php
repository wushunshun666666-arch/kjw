#!/usr/bin/env php
<?php
/**
 * Yii console bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

if (!defined('YII_ENV')) {
    define('YII_ENV', extension_loaded('is_prod_mode') ? 'prod'
        : (extension_loaded('is_test_mode') ? 'test' : 'dev'));
}
defined('YII_DEBUG') or define('YII_DEBUG', YII_ENV === 'dev');

// fcgi doesn't have STDIN and STDOUT defined by default
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../common/config/bootstrap.php');
require(__DIR__ . '/config/bootstrap.php');

$environmentConfig = require __DIR__ . '/../common/config/main-' . YII_ENV . '.php';
$consoleConfig = $environmentConfig['applications']['console'] ?? [];
unset($environmentConfig['applications']);
$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../common/config/main.php',
    require __DIR__ . '/config/main.php',
    $environmentConfig,
    $consoleConfig
);

$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);
