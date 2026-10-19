<?php

/**
 * 复制为 main-local.php，填写本站独立数据库；真实文件已被 Git 忽略。
 * dev、test、prod 环境均加载它，前台、后台和 CLI 共用本站数据库。
 */
return [
    'components' => [
        'db' => [
            'class' => yii\db\Connection::class,
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=YOUR_SITE_DATABASE',
            'username' => 'YOUR_SITE_DATABASE_USER',
            'password' => 'REPLACE_WITH_THIS_SITE_DATABASE_PASSWORD',
            'charset' => 'utf8mb4',
        ],
        'cache' => [
            'class' => yii\caching\FileCache::class,
        ],
    ],
];
