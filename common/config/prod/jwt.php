<?php

/** 线上测试 JWT 签名配置。 */
return [
    'keyId' => 'test-202609',
    'signingKeys' => ['test-202609' => '0cbed3614b0e1d15f3db0b37b8e06d387a46194c87b55cf80d9c5e2e9854e5da'],
    'issuer' => 'zhonghepan-api',
    'audience' => 'zhonghepan-client',
    'accessTokenTtl' => 7200,
    'refreshTokenTtl' => 604800,
    'clockSkew' => 30,
];
