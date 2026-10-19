<?php
/** 公告列表行。@var common\models\lottery\Notice $data */
use app\helpers\Html;
?>
<tr><td><?= Html::encode($data->id) ?></td><td><?= Html::encode($data->title) ?></td>
<td><?= Html::encode($data->enabledName) ?></td><td><?= Html::encode($data->update_time) ?></td>
<td><?= Html::navigate('详情', ['portal/notice/detail', 'id' => $data->id]) ?> <?= Html::navigate('修改', ['portal/notice/modify', 'id' => $data->id]) ?></td></tr>
