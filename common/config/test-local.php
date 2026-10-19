<?php
return yii\helpers\ArrayHelper::merge(
	require(__DIR__ . '/main.php'),
	[
		'id' => 'app-common-tests',
		'basePath' => dirname(__DIR__),
		'components' => [
			'db' => [
				'class' => 'yii\db\Connection',
				'dsn' => 'mysql:host=localhost;dbname=mancando_0_20',
				'username' => 'mancando',
				'password' => '123456',
				'charset' => 'utf8mb4',
				//'enableSchemaCache' => true,
			],
			'redis' => [
				'class' => 'mylibs\yii\redis\Redis',
				'server' => [
					'host'=>'127.0.0.1',
				],
			],
			'cache' => [
				'class' => 'mylibs\yii\caching\RedisCache',
				'keyPrefix' => 'dev',
				'redis' => 'redis',
			],
		],
	]
);
