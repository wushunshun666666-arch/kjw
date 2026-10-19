<?php
/** 开奖记录单行。@var common\models\lottery\Draw $data */
use app\helpers\Html;
?>
<tr><td><?= Html::encode($data->game->name) ?></td><td><?= Html::encode($data->issue) ?></td>
<td><?= Html::encode($data->draw_time) ?></td><td><?= Html::encode($data->numbers) ?></td>
<td><?= Html::encode($data->source) ?></td><td><?= Html::encode($data->create_time) ?></td></tr>
