<?php

/** 开奖门户应用配置，与后台共享数据库且不依赖后台命名空间。 */
return [
    'id' => 'lottery-portal',
    'name' => '开奖数据中心',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'frontend\controllers',
    'defaultRoute' => 'site/index',
    'language' => 'zh-CN',
    'timeZone' => 'Asia/Shanghai',
    'aliases' => ['@frontend' => dirname(__DIR__)],
    'components' => [
        'request' => ['enableCookieValidation' => true, 'cookieValidationKey' => getenv('LOTTERY_COOKIE_KEY') ?: bin2hex(random_bytes(32))],
        'cache' => ['class' => yii\caching\FileCache::class],
        'urlManager' => ['enablePrettyUrl' => false, 'showScriptName' => true],
        'errorHandler' => ['errorAction' => 'site/error'],
        'log' => ['targets' => [['class' => yii\log\FileTarget::class, 'levels' => ['error', 'warning'], 'logVars' => []]]],
    ],
];
