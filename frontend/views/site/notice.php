<?php
/** 公告详情。@var array $notice */
use yii\helpers\Html;
use yii\helpers\Url;
$this->title = $notice['title'] . ' · 站点公告';
?>
<div class="page-heading"><span class="eyebrow">NOTICE</span><h1><?= Html::encode($notice['title']) ?></h1><p>发布于 <?= Html::encode($notice['create_time']) ?></p></div>
<article class="prose-panel"><div class="notice-content"><?= nl2br(Html::encode($notice['content'])) ?></div><a class="text-link" href="<?= Url::to(['site/index']) ?>">← 返回开奖大厅</a></article>
