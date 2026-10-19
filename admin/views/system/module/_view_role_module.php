<?php
/**
 * _view_role_module
 * @author xurs
 */
use app\helpers\Html;
?>

<tr>
	<td><?= Html::encode($data->id) ?></td>
	<td><?= Html::encode($data->role->name) ?></td>
	<td><?= Html::encode($data->role->create_time) ?></td>
</tr>