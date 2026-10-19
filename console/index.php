#!/usr/bin/env php
<?php
/**
 * Yii console bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

if(extension_loaded('is_prod_mode')) {
	define('YII_ENV', 'prod');
}
else if(extension_loaded('is_test_mode')) {
	define('YII_ENV', 'test');
}
else {
	define('YII_ENV', 'dev');
}

if (YII_ENV == 'dev') {
	defined('YII_DEBUG') or define('YII_DEBUG', true);
}

// fcgi doesn't have STDIN and STDOUT defined by default
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../common/config/bootstrap.php');
require(__DIR__ . '/config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../common/config/main.php'),
    require(__DIR__ . '/../common/config/main-'.YII_ENV.'.php'),
    require(__DIR__ . '/config/main.php'),
    require(__DIR__ . '/config/main-'.YII_ENV.'.php')
);

$application = new yii\console\Application($config);
$exitCode = $application->run();
exit($exitCode);
