<?php
/**
 * _view_list.php
 * @author suxh
 */

use app\helpers\Html;
use yii\web\JsExpression;
?>
<tr>
	<td><?= Html::encode($data->user_id) ?></td>
	<td><?= Html::navigate($data->user->username, ['system/user/detail', 'id'=>$data->user_id], ['title'=>'查看详细信息']) ?></td>
	<td><?= Html::encode($data->user->name) ?></td>
	<td><?= Html::encode($data->user->mobi) ?></td>
	<td><?= Html::encode($data->user->create_time) ?></td>
	<td>
		<?= Html::confirmA('删除', ['system/role/delete-user', 'id'=>$data->role_id, 'user_id'=>$data->user_id], '确认删除该用户？', [
			'title'=>'删除用户',
			'success' => new JsExpression("function() {\$('#role-user-list').data('dataTable').load();}")
		]) ?>
	</td>
</tr>