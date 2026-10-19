<?php

$config = [
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'Rvy8KkEyhD-fp8vfdqJGo7HgA9qIZfYh',
        ],
    ],
	'params' => [
		'cms' => 'http://127.0.0.1/mancando/cms/admin/web/',
		'snAdmin' => 'http://127.0.0.1/mancando/sn/admin/web/',
		'pmsAdmin' => 'http://127.0.0.1/mancando/pms/admin/web/',
		'distribution_login_url' => 'http://127.0.0.1/mancando/master/distribution/web/site/tmp-login',
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
