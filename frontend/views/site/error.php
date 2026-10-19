<?php
/** 错误展示，不公开内部异常细节。@var string $name @var string $message */
use yii\helpers\Html;
use yii\helpers\Url;
$this->title = '暂时无法显示 · 开奖数据中心';
?>
<div class="empty-state"><span class="empty-symbol">!</span><h1><?= Html::encode($name) ?></h1><p><?= nl2br(Html::encode($message)) ?></p><a class="button button-primary" href="<?= Url::to(['site/index']) ?>">返回开奖大厅</a></div>
