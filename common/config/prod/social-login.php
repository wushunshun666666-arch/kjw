<?php

/**
 * 生产环境第三方登录公共配置。
 *
 * 真实平台密钥与平台启用项只能在同目录 social-login-local.php 配置，
 * 默认关闭全部平台，避免未配置完成时开放错误的授权入口。
 */

return [
    'stateTtl' => 600,
    'ticketTtl' => 120,
    'allowedReturnOrigins' => [],
    'providers' => [],
];
