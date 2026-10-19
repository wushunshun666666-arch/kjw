<?php
/**
 * _view_list.php
 * @author suxh
 */

use app\helpers\Html;
use yii\web\JsExpression;

$delRole = ['system/role/del-role', 'id'=>$data->id];
?>
<tr>
	<td><?= Html::encode($data->id) ?></td>
    <td><?= Html::encode($data->name) ?></td>
    <td><?= Html::encode($data->description) ?></td>
    <td><?= Html::encode($data->create_time) ?></td>
    <td><?= Html::encode($data->update_time) ?></td>
    <td><?= Html::navigate('修改', ['system/role/modify', 'id'=>$data->id], ['title'=>'修改角色信息']) ?>
    	<?= Html::navigate('设置权限', ['system/role/set-permission', 'id'=>$data->id], ['title'=>'设置角色权限']) ?>
    	<?= Html::navigate('查看权限', ['system/role/view-permission', 'id'=>$data->id], ['title'=>'查看角色权限']) ?>
        <?= Html::navigate('查看用户', ['system/role/user', 'id'=>$data->id], ['title'=>'查看角色用户']) ?>
		<?= Html::confirmA('删除', $delRole, '确定要删除此角色?' , [
            'title'=>'删除此角色',
            'success' => new JsExpression("function() {\$('#role-list').data('dataTable').load();}")
        ]) ?>
    </td>
</tr>