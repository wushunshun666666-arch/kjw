<?php

/** 后台站内消息中心线上环境配置。 */
return [
	'key' => 'MessageCenter$2016RiT%u',
	// 后台运营人员浏览器连接项目消息中心的Workerman端口。
	'server' => 'wss://zhong.84459.shop/notice',
	'websocketListenHost' => '0.0.0.0',
	'websocketPort' => 8700,
	'redisHost' => '127.0.0.1',
	'redisPort' => 6379,
	'redisChannel' => 'zhonghepan:admin-message',
];
