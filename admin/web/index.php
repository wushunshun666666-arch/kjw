<?php
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

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../common/config/main.php'),
    require(__DIR__ . '/../../common/config/main-'.YII_ENV.'.php'),
    require(__DIR__ . '/../config/main.php'),
    require(__DIR__ . '/../config/main-'.YII_ENV.'.php')
);

$application = new yii\web\Application($config);
$application->run();
