<?php

/** 后台站内消息中心本地开发环境配置。 */
return [
	'key' => 'MessageCenter$87T%u',
	// 本地启动的后台运营站内消息实时提醒服务端口。
	'server' => 'wss://zhong.84459.shop/notice',
	'websocketListenHost' => '0.0.0.0',
	'websocketPort' => 8700,
	'redisHost' => '127.0.0.1',
	'redisPort' => 6379,
	'redisChannel' => 'zhonghepan:admin-message',
];
