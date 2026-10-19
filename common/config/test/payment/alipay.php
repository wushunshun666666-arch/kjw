<?php
/**
 * alipay.php
 * @author James
 */

return [
	'name' 				=> 'alipay',
	'sign_type'			=> 'MD5',
	'inputCharset' 		=> 'utf-8',
	'transport' 		=> 'http',
	'gatewayUrl' 		=> 'https://mapi.alipay.com/gateway.do?',
	'httpsVerifyUrl'	=> 'https://mapi.alipay.com/gateway.do?service=notify_verify&',
	'httpVerifyUrl'		=> 'http://notify.alipay.com/trade/notify_query.do?',
	'partner'			=> '2088121026879783',
	'sellerEmail'		=> 'luolg@mancando.cn',
	'key'				=> 'wsuq3ug1mkneybyhbwvzfakfcou3p47u',
	'cacert' 			=> dirname(__FILE__).DIRECTORY_SEPARATOR.'alipay_public_key.pem',
];