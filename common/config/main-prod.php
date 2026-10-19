<?php
/**
 * main-prod.php
 * @author suxh
 */

return [
	'components' => [
		'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=zhonghepan',
            'username' => 'zhonghepan',
            'password' => 'RR5fMDmX65jjDTi4',
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
        	'keyPrefix' => 'zhonghepan',
			'redis' => 'redis',
        ],
        'jobQueue' => [
	        'class' => 'mylibs\yii\job\Queue',
	        'redis' => 'redis',
        ],
		'mailer' => [
			'class' => 'yii\symfonymailer\Mailer',
			'viewPath' => '@common/mail',
			// send all mails to a file by default. You have to set
			// 'useFileTransport' to false and configure a transport
			// for the mailer to send real emails.
            'transport' => ['dsn' => 'smtp://localhost:25'],
			//'useFileTransport' => true
		],
		'resource' => [
            'class'  => 'mylibs\api\file\FileClient',
            'server' => 'http://file.mancando.cn',
            'apiServer' => 'http://127.0.0.1/file',
            'apiKey' => 'FileApi%2015$Mancando@com',
			'uploadRedirectUrl' => ['site/upload-result'],
        ],
	],
];
