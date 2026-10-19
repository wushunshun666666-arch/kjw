<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'Rvy8KkEyhD-fp8vfdqJGo7HgA9qIZfYh',
        ],
    ],
	'params' => [
		'cms' => 'http://test.mancando.cn/cms/',
		'snAdmin' => 'http://test.mancando.cn/sn-admin/',
		'pmsAdmin' => 'http://test.mancando.cn/pms-admin',
		'distribution_login_url' => 'http://test.mancando.cn/d/site/tmp-login',
	],
];

if (!YII_ENV_TEST) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
    	'class'=>'yii\debug\Module',
    	'allowedIPs'=>['127.0.0.1', '::1', '10.0.2.*'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
    	'class'=>'yii\gii\Module',
    	'allowedIPs'=>['127.0.0.1', '::1', '10.0.2.*'],
	];
}

return $config;
