<?php

/** 开发环境：复制到 common/config 后填写；各站真实配置不提交 Git。 */
return [
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=YOUR_DEV_DATABASE',
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
            'endpoint' => '', // client：中心 https://域名/index.php
            'client_id' => '',
            'token' => '', // 仅分站服务器保存明文密钥。
            'clients' => [], // center：客户编号 => ['token_hash' => SHA256摘要, 'enabled' => true]
            'cache_seconds' => 2,
            'stale_seconds' => 1800,
        ],
    ],
    'applications' => [
        'frontend' => ['components' => ['request' => ['cookieValidationKey' => 'REPLACE_WITH_A_RANDOM_COOKIE_KEY']]],
        'admin' => [
            'components' => ['request' => ['cookieValidationKey' => 'REPLACE_WITH_A_RANDOM_COOKIE_KEY']],
            'bootstrap' => ['debug', 'gii'],
            'modules' => [
                'debug' => ['class' => yii\debug\Module::class, 'allowedIPs' => ['127.0.0.1', '::1']],
                'gii' => ['class' => yii\gii\Module::class, 'allowedIPs' => ['127.0.0.1', '::1']],
            ],
        ],
        'console' => ['bootstrap' => ['gii'], 'modules' => ['gii' => yii\gii\Module::class]],
    ],
];
