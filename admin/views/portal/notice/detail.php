<?php
/** 公告只读详情。@var common\models\lottery\Notice $data */
use app\helpers\Html;
?>
<div class="card shadow-sm mb-3"><div class="card-header"><h1 class="card-title h6 mb-0">公告详情</h1></div><div class="card-body"><dl class="row">
<dt class="col-sm-3">标题</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->title)) ?></dd>
<dt class="col-sm-3">正文</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->content)) ?></dd>
<dt class="col-sm-3">发布状态</dt><dd class="col-sm-9"><?= Html::encode($data->enabledName) ?></dd>
</dl></div><div class="card-footer"><?= Html::backButton() ?></div></div>
