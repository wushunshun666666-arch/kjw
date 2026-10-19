<?php
/** 彩种只读详情。@var common\models\lottery\Game $data */
use app\helpers\Html;
?>
<div class="card shadow-sm mb-3"><div class="card-header"><h1 class="card-title h6 mb-0">彩种详情</h1></div><div class="card-body"><dl class="row">
<dt class="col-sm-3">彩种代码</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->code)) ?></dd>
<dt class="col-sm-3">彩种名称</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->name)) ?></dd>
<dt class="col-sm-3">彩种说明</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->description)) ?></dd>
<dt class="col-sm-3">号码个数</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->ball_count)) ?></dd>
<dt class="col-sm-3">最小号码</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->ball_min)) ?></dd>
<dt class="col-sm-3">最大号码</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->ball_max)) ?></dd>
<dt class="col-sm-3">排序</dt><dd class="col-sm-9 text-break"><?= nl2br(Html::encode($data->sort_order)) ?></dd>
<dt class="col-sm-3">发布状态</dt><dd class="col-sm-9"><?= Html::encode($data->enabledName) ?></dd>
</dl></div><div class="card-footer"><?= Html::backButton() ?></div></div>
