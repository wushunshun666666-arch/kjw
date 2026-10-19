<?php
/** 参考前端应用外壳。 */
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use frontend\assets\PortalAsset;
if ($this->params['multiGame'] ?? false) { \frontend\assets\GamePortalAsset::register($this); }
else { PortalAsset::register($this); }
$appearance = $this->params['portalAppearance'] ?? ['website' => '', 'title' => '官方授权开奖网'];
$routes = [];
foreach (['index/website' => 'website', 'index/website/open' => 'open', 'index/notice/list' => 'notices', 'index/notice/detail' => 'detail'] as $source => $action) {
    $routes[$source] = Url::to(['reference/' . $action]);
}
$this->registerJs('window.appData = {api:"", source:""}; window.portalWebsite = ' . Json::htmlEncode($appearance['website']) . '; window.portalApiRoutes = ' . Json::htmlEncode($routes) . '; window.portalQrcode = ' . Json::htmlEncode(Url::to(['reference/qrcode'])) . ';', \yii\web\View::POS_HEAD);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html><html lang="zh"><head>
<meta charset="UTF-8"><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes"><meta name="renderer" content="webkit">
<link rel="icon" href="data:image/gif;base64,R0lGO==" type="image/gif">
<title><?= Html::encode($this->title ?: '官方授权开奖网') ?></title><?php $this->head() ?>
</head><body><?php $this->beginBody() ?>
<?php if (!empty($this->params['portalGames'])): ?>
<nav class="portal-game-nav" aria-label="彩种切换">
<?php foreach ($this->params['portalGames'] as $game): ?>
<?= Html::a(Html::encode($game['name']), ['site/index', 'game' => $game['code'], '#' => '/'], ['class' => ($this->params['portalGame'] ?? 'jnd28') === $game['code'] ? 'selected' : '', 'aria-current' => ($this->params['portalGame'] ?? 'jnd28') === $game['code'] ? 'page' : null]) ?>
<?php endforeach; ?>
</nav>
<?php endif; ?>
<?= $content ?><?php $this->endBody() ?></body></html><?php $this->endPage() ?>
