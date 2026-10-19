<?php

/** 初始迁移默认外观及服务端接入选项。上线后链接、广告和图片由后台数据库配置管理。 */
return [
    'website' => getenv('LOTTERY_PUBLIC_DOMAIN') ?: 'jnd68.com',
    'title' => '官方授权开奖网',
    'banner' => '/assets1/banner.jpg',
    'icon' => '/assets1/icon.jpg',
    'show_qrcode' => 1,
    'landing' => getenv('LOTTERY_LANDING_URL') ?: 'https://cok11.n9pvn12f.lol:3399?t=1790929762',
    'ad_wx' => '//hzhb05.vip',
    'ad_ww' => '',
    'ad_icon' => '',
    'ad' => [[
        'cover' => '/assets1/ad.gif',
        'link' => getenv('LOTTERY_AD_URL') ?: 'https://tfajn.hcjwj.com/app/register.php?site_id=2081&pt=B9C93FB4-B763-B3D9-588E-2163C860FA12',
    ]],
    'notice_scroll' => '各项福利介绍请看右方公告！！！各项福利介绍请看右方公告！！！各项福利介绍请看右方公告！！！',
    'reference_notices' => getenv('LOTTERY_REFERENCE_NOTICES') !== '0',
];
