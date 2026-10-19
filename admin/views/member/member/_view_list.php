<?php

/**
 * 后台会员列表单行模板。
 *
 * @var common\models\member\Member $data 当前会员记录
 */

use app\helpers\Html;
use yii\web\JsExpression;

$reload = new JsExpression("function() { $('#member-list').data('dataTable').load(); }");
$username = $data->username ?: '未设置';
$mobile = $data->mobile ?: '未设置手机号';
$email = $data->email ?: '未设置Email';
$account = $data->account;
$parent = $data->parent;
?>
<tr>
    <td><?= Html::encode($data->id) ?></td>
    <td>
        <?= Html::navigate(
            Html::encode($username),
            ['member/member/detail', 'id' => $data->id],
            ['title' => '查看会员详情']
        ) ?>
        <div class="text-muted small"><?= Html::encode($data->nickname) ?></div>
        <div class="text-muted small">推广码：<?= Html::encode($data->invite_code) ?></div>
        <div class="text-muted small">
            上级：<?= $parent === null ? '无' : Html::encode($parent->username ?: $parent->nickname) ?>
        </div>
    </td>
    <td>
        <div><?= Html::encode($mobile) ?></div>
        <div class="text-muted small"><?= Html::encode($email) ?></div>
    </td>
    <td><?= Html::encode($account?->available_amount ?? '账户异常') ?></td>
    <td><?= Html::encode($account?->frozen_amount ?? '账户异常') ?></td>
    <td><?= Html::encode($data->registerTypeName) ?></td>
    <td><?= Html::encode($data->statusName) ?></td>
    <td><?= Html::encode(Yii::$app->formatter->asDatetime($data->create_time)) ?></td>
    <td>
        <?= Html::navigate('客服', ['kefu/kefu/index', 'member_id' => $data->id], ['title' => '进入客服工作台']) ?>
        <?= Html::navigate('修改', ['member/member/modify', 'id' => $data->id], ['title' => '修改会员']) ?>
        <?php if ($data->isActive()) : ?>
            <?= Html::confirmA('冻结', ['member/member/freeze', 'id' => $data->id], '确定要冻结该会员吗？', [
                'title' => '冻结会员',
                'success' => $reload,
            ]) ?>
        <?php else : ?>
            <?= Html::confirmA('解冻', ['member/member/unfreeze', 'id' => $data->id], '确定要解冻该会员吗？', [
                'title' => '解冻会员',
                'success' => $reload,
            ]) ?>
        <?php endif; ?>
    </td>
</tr>
