<?php
/** 参考前端应用外壳。 */
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use frontend\assets\PortalAsset;
PortalAsset::register($this);
$appearance = $this->params['portalAppearance'] ?? ['website' => '', 'title' => '官方授权开奖网'];
$routes = [];
foreach (['index/website' => 'website', 'index/website/open' => 'open', 'index/notice/list' => 'notices', 'index/notice/detail' => 'detail'] as $source => $action) {
    $routes[$source] = Url::to(['reference/' . $action]);
}
$this->registerJs('window.appData = {api:"", source:""}; window.portalWebsite = ' . Json::htmlEncode($appearance['website']) . '; window.portalApiRoutes = ' . Json::htmlEncode($routes) . '; window.portalQrcode = ' . Json::htmlEncode(Url::to(['reference/qrcode'])) . ';', \yii\web\View::POS_HEAD);
$this->registerJs('window.portalGameLinks = ' . Json::htmlEncode($this->render('/site/_game_links'))
    . '; window.portalGameCurrent = ' . Json::htmlEncode($this->render('/site/_game_current'))
    . '; window.portalGameHistory = ' . Json::htmlEncode($this->render('/site/_game_history'))
    . '; window.portalHistoryPagination = ' . Json::htmlEncode($this->render('/site/_history_pagination'))
    . '; window.portalHistoryEndpoint = ' . Json::htmlEncode(Url::to(['reference/history']))
    . '; window.portalInitialGame = ' . Json::htmlEncode($this->params['portalGame'] ?? 'jnd28')
    . '; window.portalInitialData = ' . Json::htmlEncode($this->params['portalGameData'] ?? null)
    . '; window.portalGameEndpoint = ' . Json::htmlEncode(Url::to(['reference/game']))
    . '; window.portalTitle = ' . Json::htmlEncode($appearance['title']) . ';', \yii\web\View::POS_HEAD);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html><html lang="zh"><head>
<meta charset="UTF-8"><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="mobile-web-app-capable" content="yes"><meta name="renderer" content="webkit">
<link rel="icon" href="data:image/gif;base64,R0lGO==" type="image/gif">
<title><?= Html::encode($this->title ?: '官方授权开奖网') ?></title><?php $this->head() ?>
</head><body><?php $this->beginBody() ?>
<?= $content ?><?php $this->endBody() ?></body></html><?php $this->endPage() ?>
