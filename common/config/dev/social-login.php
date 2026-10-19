<?php

/**
 * 本地开发第三方登录配置。
 *
 * 仅用于验证完整授权流程，所有平台均指向内部测试适配器；
 * 真实平台 App Secret 必须写入同目录 social-login-local.php。
 */

use common\models\member\MemberAuthIdentity;
use common\oauth\TestSocialOAuthAdapter;

return [
    'stateTtl' => 600,
    'ticketTtl' => 120,
    'allowedReturnOrigins' => [
        'http://127.0.0.1:5180',
        'http://localhost:5180',
    ],
    'providers' => [
        MemberAuthIdentity::PROVIDER_QQ => [
            'enabled' => true,
            'class' => TestSocialOAuthAdapter::class,
            'appId' => 'test-qq-app',
        ],
        MemberAuthIdentity::PROVIDER_WECHAT => [
            'enabled' => true,
            'class' => TestSocialOAuthAdapter::class,
            'appId' => 'test-wechat-official-account',
        ],
        MemberAuthIdentity::PROVIDER_ALIPAY => [
            'enabled' => true,
            'class' => TestSocialOAuthAdapter::class,
            'appId' => 'test-alipay-app',
        ],
        MemberAuthIdentity::PROVIDER_FACEBOOK => [
            'enabled' => true,
            'class' => TestSocialOAuthAdapter::class,
            'appId' => 'test-facebook-app',
        ],
        MemberAuthIdentity::PROVIDER_X => [
            'enabled' => true,
            'class' => TestSocialOAuthAdapter::class,
            'appId' => 'test-x-app',
        ],
        MemberAuthIdentity::PROVIDER_TELEGRAM => [
            'enabled' => true,
            'class' => TestSocialOAuthAdapter::class,
            'appId' => 'test-telegram-bot',
        ],
    ],
];
