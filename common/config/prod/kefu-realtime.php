<?php

/** 客服 WebSocket 线上测试配置。 */
return [
    'keyId' => 'test-202609',
    'signingKey' => 'bWExpMNl4OZUHVIddef+qIrqx4YNBxk9YBTCAP12TdY2dWIJMfa5tLvk8H2RUpZs',
    'previousKeys' => [],
    // 客户端通过 HTTPS 域名访问 Nginx，再由 Nginx 反向代理至本机 Workerman。
    'server' => 'wss://api.84459.shop/kefu-ws',
    'websocketListenHost' => '127.0.0.1',
    'websocketPort' => 8600,
    // Channel 仅供同机 PHP 与 Workerman 进程通信，不向公网暴露。
    'channelListenHost' => '127.0.0.1',
    'channelHost' => '127.0.0.1',
    'channelPort' => 2206,
];
