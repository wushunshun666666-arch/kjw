<?php
/**
 * main-dev.php
 * @author suxh
 */

$config = [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=xinshengkaijiang',
            'username' => 'xinshengkaijiang',
            'password' => 'GpKDGDrH4JbeP64w',
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
            //'useFileTransport' => true,
        ],
        'resource' => [
            'class'  => 'mylibs\api\file\FileClient',
            'server' => 'http://127.0.0.1/mancando/file/client/web',
            'apiKey' => 'FileApi%200$Man@com',
        	'uploadRedirectUrl' => ['site/upload-result'],
        ],
    ],
];

return (require __DIR__ . '/merge-local.php')($config);
