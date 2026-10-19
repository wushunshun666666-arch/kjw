<?php
/** 彩种列表行。@var common\models\lottery\Game $data */
use app\helpers\Html;
?>
<tr><td><?= Html::encode($data->id) ?></td><td><?= Html::encode($data->name) ?></td>
<td><?= Html::encode($data->enabledName) ?></td><td><?= Html::encode($data->update_time) ?></td>
<td><?= Html::navigate('详情', ['portal/game/detail', 'id' => $data->id]) ?> <?= Html::navigate('修改', ['portal/game/modify', 'id' => $data->id]) ?></td></tr>
