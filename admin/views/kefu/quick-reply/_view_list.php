<?php

/** @var common\models\kefu\KefuQuickReply $data 当前快捷回复 */

use app\helpers\Html;
use yii\web\JsExpression;

// 状态切换成功后刷新快捷回复列表。
$reload = new JsExpression("function(){ $('#quick-reply-list').data('dataTable').load(); }");
?>
<tr>
    <td><?= (int) $data->id ?></td>
    <td><?= Html::encode($data->title) ?></td>
    <td><?= nl2br(Html::encode($data->content)) ?></td>
    <td><?= (int) $data->orderby ?></td>
    <td><?= Html::encode($data->statusName) ?></td>
    <td>
        <?= Html::navigate('修改', ['modify', 'id' => $data->id]) ?>
        <?php if ($data->isActive()) : ?>
            <?= Html::confirmA('停用', ['disable', 'id' => $data->id], '确定停用该快捷回复吗？', ['success' => $reload]) ?>
        <?php else : ?>
            <?= Html::confirmA('启用', ['enable', 'id' => $data->id], '确定启用该快捷回复吗？', ['success' => $reload]) ?>
        <?php endif; ?>
    </td>
</tr>
