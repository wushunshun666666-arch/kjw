<?php
/**
 * wechat.php
 * @author James
 */

$baseUrl = 'http://test.mancando.cn/md';
$securityCheckUrl = 'http://www.aem315.net/823/wx_search.jsp';
$securityCheckUrlNew = 'http://mobile.fangwei315.com';
$contactUsUrl = 'http://qr17.cn/McBFMW';
$joinUrl = 'http://mancando.mikecrm.com/pvuYda';
$newsUrl = 'http://mp.weixin.qq.com/mp/homepage?__biz=MzI0NjE0MzE0Mw==&hid=1&sn=a49ed9c624d64a0f5e85f33112083f21#wechat_redirect';
return [
	'token'			=> '8oGkRrydGAE9LFb',
	'appid'			=> 'wxfbb2a76afb95665a',
	'appsecret'		=> '6560b59a967ca3860fa58e660edab9e7',
	'encodingKey'	=> '2bZvMTvZ0f8oGkRrydGAE9LFbQZAeRyiQiJHbTn7KdW',
	'normalMenu'	=> [
		['name' => '产品查询', 'sub_button' => [
			['name'=>'查配件', 'type'=>'view', 'url'=>$baseUrl.'/guest'],
			['name'=>'防伪查询', 'type'=>'view', 'url'=>$securityCheckUrl],
			['name'=>'防伪查询(新)', 'type'=>'view', 'url'=>$securityCheckUrlNew],
		]],
		['name'=>'绑定账号', 'type'=>'view', 'url'=>$baseUrl, 'auth'=>true, 'state'=>'binding'],
		['name'=>'联系我们', 'type'=>'view', 'url'=>$contactUsUrl],
	],
	'distributionMenu'	=> [
		['name' => '产品查询', 'sub_button' => [
			['name'=>'查配件', 'type'=>'view','url'=>$baseUrl, 'auth'=>true, 'state'=>'searchProduct'],
			['name'=>'防伪查询', 'type'=>'view', 'url'=>$securityCheckUrl],
			['name'=>'防伪查询(新)', 'type'=>'view', 'url'=>$securityCheckUrlNew],
		]],
		['name'=>'销售单', 'type'=>'view', 'url'=>$baseUrl, 'auth'=>true, 'state'=>'salesOrder'],
		['name'=>'我的', 'sub_button'=>[
			['name'=>'客户管理', 'type'=>'view', 'url'=>$baseUrl, 'auth'=>true, 'state'=>'searchCustomer'],
			['name'=>'帐户&充值', 'type'=>'view', 'url'=>$baseUrl, 'auth'=>true, 'state'=>'account'],
			['name'=>'联系客服', 'type'=>'click', 'key'=>'EVENT_KEFU'],
		]],
	],
	'subscribeMessage' => "感谢您关注三头六臂汽配，我们等您很久啦/:handclap/:handclap/:handclap\n\n【查配件】点击左下角的【查配件】功能查询\n\n【绑定账号】绑定三头六臂商家账号，绑定成功后可查产品价格，管理客户\n\n【联系我们】服务热线：400-999-3115",
	'defaultReplay' => "您好，如果需要查配件 ①您可以手动输入车型简写（例如：宝马x1可输入“x1”，标致408可输入“408”）②您可以直接输入车名拼音简写（例如：凯越输入 “ky”，卡罗拉输入 “KLL”）③您也可以直接语音输入车型车系（例如：语音说“凯美瑞”）。#如需联系客服请直接输入“01”。",

	'guestUrl'		=> $baseUrl.'/guest/',
	'distributionUrl'	=> $baseUrl,
];
