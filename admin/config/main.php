<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/params.php')
);

return [
    'id' => 'app-admin',
    'name' => '管理系统',
    'version' => '1.15',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'user' => [
            'identityClass' => 'common\models\system\UserIdentity',
            'authTimeout' => 7200,
        ],
        'urlManager' => [
	        'enablePrettyUrl' => true,
	        'showScriptName' => false,
	        'enableStrictParsing' => false,
	        'rules' => [

	        ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'session' => [
			'class'=>'yii\web\CacheSession',
			'useCookies'=>true,
       		'timeout' => 7200,
        	'name' => 'ASESSID',
        ],
    ],
    'params' => $params,
];
