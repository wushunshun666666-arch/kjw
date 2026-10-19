<?php
/**
 * _view_list.php
 * @author xurs
 */

use app\helpers\Html;
use yii\web\JsExpression;
$delModule = ['system/module/delete', 'id'=>$data->id];

?>

<tr>
	
	<td><?= Html::encode($data->id) ?></td>
	<td><?= Html::encode($data->name) ?></td>
    <td><?= Html::encode($data->url) ?></td>
    <td><?= Html::encode($data->description) ?></td>
    <td><?= Html::encode($data->create_time) ?></td>
    <td><?= Html::encode($data ->update_time)?></td>
	<td>
		<?= Html::navigate('查看', ['system/module/detail', 'id'=>$data->id], ['title'=>'查看模型信息']) ?>
		<?= Html::navigate('修改', ['system/module/modify', 'id'=>$data->id], ['title'=>'修改模块信息']) ?>
		<?= Html::confirmA('删除', $delModule, '确定要删除此模块?' , [
            'title'=>'删除此模块',
            'success' => new JsExpression("function() {\$('#module-list').data('dataTable').load();}")
        ]) ?>
	</td>
</tr>