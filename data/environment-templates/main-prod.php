<?php

/** 生产环境：每个主站/分站只修改自己这一份，真实文件不提交 Git。 */
return [
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=YOUR_PRODUCTION_DATABASE',
            'username' => 'YOUR_DATABASE_USER',
            'password' => '',
            'charset' => 'utf8mb4',
        ],
        // 每个站点使用自己的 runtime，防止多个分站共用文件缓存。
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
            'endpoint' => '', // client：中心 https://域名/index.php（不带查询参数）。
            'client_id' => '', // client：中心分配的客户编号。
            'token' => '', // client：本站密钥，禁止发给浏览器。
            'clients' => [], // center：客户编号 => ['token_hash' => SHA256摘要, 'enabled' => true]
            'cache_seconds' => 2,
            'stale_seconds' => 1800,
        ],
    ],
    'applications' => [
        'frontend' => ['components' => ['request' => ['cookieValidationKey' => 'REPLACE_WITH_A_RANDOM_COOKIE_KEY']]],
        'admin' => ['components' => ['request' => ['cookieValidationKey' => 'REPLACE_WITH_A_RANDOM_COOKIE_KEY']]],
    ],
];
