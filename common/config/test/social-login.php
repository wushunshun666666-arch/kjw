<?php

/**
 * 自动化测试第三方登录配置。
 *
 * 测试环境复用内部测试适配器，禁止配置真实外部平台密钥。
 */

return require(__DIR__ . '/../dev/social-login.php');
