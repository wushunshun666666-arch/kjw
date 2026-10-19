<?php

/** 后台站内消息中心测试环境配置。 */
return [
	'key' => 'MessageCenter$87T%u',
	// 测试环境复用本机消息中心端口，避免连接旧项目域名。
	'server' => 'ws://127.0.0.1:8700/',
	'websocketListenHost' => '0.0.0.0',
	'websocketPort' => 8700,
	'redisHost' => '127.0.0.1',
	'redisPort' => 6379,
	'redisChannel' => 'zhonghepan:admin-message:test',
];
