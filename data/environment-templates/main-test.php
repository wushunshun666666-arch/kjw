<?php

/** 测试环境：使用独立测试库，禁止填写生产库。 */
return [
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=YOUR_TEST_DATABASE',
            'username' => 'YOUR_DATABASE_USER',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
        'cache' => ['class' => yii\caching\FileCache::class],
        'mailer' => [
            'class' => yii\symfonymailer\Mailer::class,
            'viewPath' => '@common/mail',
            'useFileTransport' => true,
        ],
    ],
    'params' => [
        'lotteryData' => [
            // standalone 本站采集；center 中心采集；client 分站从中心读取。
            'mode' => 'standalone',
            'endpoint' => '',
            'client_id' => '',
            'token' => '',
            'clients' => [],
            'cache_seconds' => 2,
            'stale_seconds' => 1800,
        ],
    ],
    'applications' => [
        'frontend' => ['components' => ['request' => ['cookieValidationKey' => 'REPLACE_WITH_A_RANDOM_COOKIE_KEY']]],
        'admin' => ['components' => ['request' => ['cookieValidationKey' => 'REPLACE_WITH_A_RANDOM_COOKIE_KEY']]],
    ],
];
