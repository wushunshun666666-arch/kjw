<?php
return [
	'language' =>'zh-CN',
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
    ],
    'components' => [
    	'formatter' => [
    		'nullDisplay' => '--',
    	],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
            	// 异常错误日志统一记录到app.log
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => YII_DEBUG ? ['error', 'warning', 'info', 'trace'] : ['error', 'warning'],
                	'except' => ['yii\web\HttpException:404', 'yii\web\HttpException:403', 'yii\web\HttpException:400'],
					'logVars' => [],
                ],
            	// 异常错误日志发送到管理员邮箱
            	[
            		'class' => 'mylibs\yii\log\EmailTarget',
            		'mailer' => 'mailer',
            		'message' => [
            			'from' => ['log@mancando.cn'],
            			'to' => ['admin@localhost'],
            			'subject' => 'Log message',
            		],
            		'levels' => ['error', 'warning'],
            		'except' => ['yii\web\HttpException:404', 'yii\web\HttpException:403', 'yii\web\HttpException:400'],
            	],
            	// 404、403、400错误日志单独记录
				[
            		'class' => 'mylibs\yii\log\FileTarget',
            		'levels' => ['error'],
					'categories' => ['yii\web\HttpException:404', 'yii\web\HttpException:403', 'yii\web\HttpException:400'],
					'logVars' => [],
				],
            	// 其它应用日志单独记录到各自的文件中
            	[
            		'class' => 'mylibs\yii\log\FileTarget',
            		'levels' => ['info', 'trace'],
					'except' => ['application', 'yii\*'],
					'logVars' => [],
            	],
            ],
        ],
    	'delay' => [
    		'class' => 'mylibs\yii\events\DelayEvents',
    	],
		'assetManager' => [
			'appendTimestamp' => true,
			'bundles' => [
				'yii\web\JqueryAsset' => [
					'sourcePath' => '@mylibs/yii/ui/adminLte/assets/jquery',
					'js' => [
						'jquery.min.js',
						'jquery-migrate.min.js',
					],
				],
			],
		],
    ],
];
