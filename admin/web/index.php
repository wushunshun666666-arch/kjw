<?php
if (!defined('YII_ENV')) {
    define('YII_ENV', extension_loaded('is_prod_mode') ? 'prod'
        : (extension_loaded('is_test_mode') ? 'test' : 'dev'));
}
defined('YII_DEBUG') or define('YII_DEBUG', YII_ENV === 'dev');

require(__DIR__ . '/../../vendor/autoload.php');
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../../common/config/bootstrap.php');
require(__DIR__ . '/../config/bootstrap.php');

$environmentConfig = require __DIR__ . '/../../common/config/main-' . YII_ENV . '.php';
$adminConfig = $environmentConfig['applications']['admin'] ?? [];
unset($environmentConfig['applications']);
$config = yii\helpers\ArrayHelper::merge(
    require __DIR__ . '/../../common/config/main.php',
    require __DIR__ . '/../config/main.php',
    $environmentConfig,
    $adminConfig
);

$application = new yii\web\Application($config);
$application->run();
