<?php
/** 渲染公开彩种链接，供服务端页面和参考前端的广告下方导航共用。 */
use yii\helpers\Html;
?>
<?php foreach ($this->params['portalGames'] ?? [] as $game): ?>
<?= Html::a(Html::encode($game['name']), ['site/index', 'game' => $game['code'], '#' => '/'], ['data-game' => $game['code'], 'class' => ($this->params['portalGame'] ?? 'jnd28') === $game['code'] ? 'selected' : '', 'aria-current' => ($this->params['portalGame'] ?? 'jnd28') === $game['code'] ? 'page' : null]) ?>
<?php endforeach; ?>
