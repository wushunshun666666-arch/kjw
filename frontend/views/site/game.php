<?php
/** 五位数与赛车类开奖页面。@var array $data @var array $appearance */
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
$this->title = $data['game']['name'] . ' - ' . $appearance['title'];
$this->registerJs('window.portalGameData = ' . Json::htmlEncode($data) . '; window.portalGameUrl = ' . Json::htmlEncode(Url::to(['reference/game', 'game' => $data['game']['code']])) . ';', \yii\web\View::POS_HEAD);
?>
<main class="multi-portal" id="multi-portal">
    <header class="portal-top"><strong><?= Html::encode($appearance['website']) ?></strong><nav aria-label="网站导航"><?= Html::a('首页', ['site/index', '#' => '/']) ?><?= Html::a('公告', ['site/index', '#' => '/notice']) ?><?= Html::a('数据由来', ['site/index', '#' => '/rule']) ?><?= Html::a('联系合作', ['site/index', '#' => '/contact']) ?></nav></header>
    <div class="portal-banner">
        <img class="portal-banner-img" src="<?= Html::encode($appearance['banner']) ?>" alt="网站横幅">
        <?php if ($appearance['show_qrcode']): ?><a class="portal-qr" href="<?= Html::encode($appearance['landing']) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= Html::encode(Url::to(['reference/qrcode'])) ?>" alt="登录二维码"></a><?php endif; ?>
    </div>
    <div class="portal-entry"><a href="<?= Html::encode($appearance['landing']) ?>" target="_blank" rel="noopener noreferrer">登录或注册！</a><button type="button" data-action="ads" aria-expanded="true">收起广告</button></div>
    <div data-region="ads"><?php foreach ($appearance['ad'] as $ad): ?><a class="portal-ad" href="<?= Html::encode($ad['link']) ?>" target="_blank" rel="noopener noreferrer"><img src="<?= Html::encode($ad['cover']) ?>" alt="广告"></a><?php endforeach; ?></div>
    <?php if ($appearance['notice_scroll']): ?><p class="portal-notice"><?= Html::encode($appearance['notice_scroll']) ?></p><?php endif; ?>
    <section class="portal-current" aria-label="最新开奖">
        <div class="portal-current-heading"><h1><?= Html::encode($data['game']['name']) ?></h1><button type="button" data-action="refresh">刷新</button></div>
        <p data-region="issue">等待采集数据</p><div class="portal-balls" data-region="balls"></div>
        <p class="portal-current-summary" data-region="summary"></p><p class="portal-state" data-region="status" role="status"></p>
    </section>
    <nav class="portal-tabs" role="tablist" aria-label="开奖信息">
        <button type="button" role="tab" id="tab-result" aria-selected="true" aria-controls="portal-game-panel" data-tab="result">结果</button>
        <button type="button" role="tab" id="tab-trend" aria-selected="false" aria-controls="portal-game-panel" data-tab="trend">走势</button>
        <button type="button" role="tab" id="tab-statistics" aria-selected="false" aria-controls="portal-game-panel" data-tab="statistics">统计</button>
    </nav>
    <div class="portal-options">
        <label data-region="position-control" hidden>位置 <select data-field="position" aria-label="号码位置"><?php foreach ($data['game']['positions'] as $index => $label): ?><option value="<?= $index ?>"><?= Html::encode($label) ?></option><?php endforeach; ?></select></label>
        <label>显示 <select data-field="limit" aria-label="显示期数"><option value="20">最近20期</option><option value="50">最近50期</option><option value="100" selected>最近100期</option></select></label>
        <span data-region="samples"></span>
    </div>
    <section id="portal-game-panel" role="tabpanel" aria-labelledby="tab-result" tabindex="0"><div class="portal-table-scroll" data-region="table"></div></section>
    <p class="portal-rules"><?= Html::encode($data['game']['rule']) ?>走势与统计使用本站已采集的实际记录。</p>
    <noscript>请启用 JavaScript 查看开奖结果、走势和统计。</noscript>
</main>
