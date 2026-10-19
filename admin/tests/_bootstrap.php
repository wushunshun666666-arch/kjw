<?php

/**
 * 后台会员测试启动文件。
 *
 * 加载Composer、Yii框架和项目别名，使后台命名空间可在单元测试中解析。
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__, 2) . '/common/config/bootstrap.php';

Yii::setAlias('app', dirname(__DIR__));
