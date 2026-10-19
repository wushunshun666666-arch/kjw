<?php
/**
 * _view_list.php
 * @author suxh
 */

use app\helpers\Html;
use yii\web\JsExpression;

$resetPwdUrl = ['system/user/reset-password', 'id'=>$data->id];
$closeUrl = ['system/user/close', 'id'=>$data->id];
?>
<tr>
	<td><?= Html::encode($data->id) ?></td>
	<td>
		<?php if (!$data->isClose()):?>
		<?= Html::navigate($data->username, ['system/user/detail', 'id'=>$data->id], ['title'=>'查看详细信息']) ?>
		<?php else:?>
		<?= Html::encode($data->username) ?>
		<?php endif;?>
	</td>
    <td><?= Html::encode($data->name) ?></td>
    <td><?= Html::encode($data->department->name) ?></td>
    <td><?= Html::encode($data->create_time) ?></td>
    <td><?= Html::encode($data->statusName) ?></td>
    <td>
    	<?php if (!$data->isClose()):?>
	     	<?= Html::navigate('修改', ['system/user/modify', 'id'=>$data->id], ['title'=>'修改用户信息']) ?>
	       	<?= Html::confirmA('密码', $resetPwdUrl, '确定要重置该用户的登录密码？', ['title'=>'重置登录密码']) ?>

	    	<?php if (!$data->isAdmin() && !$data->isNoLogin()):?>
	    	<?= Html::navigate('权限', ['system/user/set-permission', 'id'=>$data->id], ['title'=>'设置用户权限']) ?>
	    	<?= Html::confirmA('注销', $closeUrl, '确定要注销该用户吗？', [
	    		'title'=>'注销用户',
	    		'success' => new JsExpression("function() {\$('#user-list').data('dataTable').load();}")
	    	]) ?>
	    	<?php endif;?>
    	<?php endif;?>
    </td>
</tr>