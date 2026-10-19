<?php

/**
 * 复制为 lottery-data-local.php 后按部署角色配置；私有文件已被 Git 忽略。
 * 默认 standalone 保留本站采集；center 采集一次并提供只读 API；client 仅读取中心。
 * 各站广告、公告、登录链接、后台账号和展示开关继续使用各自的数据库。
 * 为每个客户生成独立随机密钥（例如 bin2hex(random_bytes(32))），只在该客户服务器保存明文。
 * 中心 clients 仅保存 hash('sha256', 客户密钥)，禁用客户时将 enabled 改为 false。
 * client 的 endpoint 填中心完整 https://域名/index.php，不包含任何查询参数。
 * API 只由客户服务器调用，不能把 token、client_id 或 clients 注入浏览器。
 */
return [
    'mode' => 'standalone',
    'endpoint' => '',
    'client_id' => '',
    'token' => '',
    'clients' => [
        // 'customer_a' => ['token_hash' => '填写64位小写SHA-256摘要', 'enabled' => true],
    ],
    'cache_seconds' => 2,
    'stale_seconds' => 1800,
];
