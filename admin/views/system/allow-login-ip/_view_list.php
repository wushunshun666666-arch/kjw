<?php

use app\helpers\Html;
use yii\web\JsExpression;

$deleteUrl = ['system/allow-login-ip/delete', 'id' => $data->id];
?>
<tr>
	<td><?= Html::encode($data->ip) ?></td>
	<td><?= Html::encode($data->ownership) ?></td>
	<td><?= Html::encode($data->note) ?></td>
	<td><?= Html::encode($data->create_time) ?></td>
	<td><?= Html::encode($data->update_time) ?></td>
	<td>
		<?= Html::navigate('修改', ['system/allow-login-ip/modify', 'id' => $data->id], ['title' => '修改可登录IP']) ?>
		<?= Html::confirmA('删除', $deleteUrl, '确定要删除此可登录IP吗？', [
			'title' => '删除可登录IP',
			'success' => new JsExpression("function() { $('#allow-login-ip-list').data('dataTable').load(); }")
		]) ?>
	</td>
</tr>
