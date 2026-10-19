<?php
/**
 * main-prod.php
 * @author suxh
 */

return [
	'components' => [
		'request' => [
			// !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
			'cookieValidationKey' => 'P1itThtID0xiF3klADFWMYzKtTwM2Cwa',
		],
		'session' => [
			'cookieParams' => ['httponly' => true, 'domain'=>'mancando.cn'],
		],
	],
	'params' => [
		'cms' => 'http://cms.mancando.cn',
		'snAdmin' => 'http://sn-admin.mancando.cn/',
		'pmsAdmin' => 'http://pms-admin.mancando.cn/',
		'distribution_login_url' => 'http://d.mancando.cn/site/tmp-login',
	],
];
