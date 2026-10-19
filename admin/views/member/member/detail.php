<?php

/**
 * 后台会员详情页面。
 *
 * @var common\models\member\Member $model 会员详情实体
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/** @var common\models\member\Account|null $account 会员一对一账户 */
$account = $model->account;
?>
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title h6 mb-0">会员详情</h3>
    </div>
    <?php
    /** @var ActiveForm $form 会员详情只读表单组件 */
    $form = ActiveForm::begin(['layout' => 'horizontal']);
    ?>
    <div class="card-body">
        <?= $form->field($model, 'id')->readonly() ?>
        <?= $form->field($model, 'invite_code')->readonly() ?>
        <?= $form->fieldReadonly(
            '直属上级',
            $model->parent === null
                ? '无（根会员）'
                : sprintf('%s（ID：%d）', $model->parent->username ?: $model->parent->nickname, $model->parent->id)
        ) ?>
        <?= $form->field($model, 'username')->readonly(['value' => $model->username ?: '未设置']) ?>
        <?= $form->field($model, 'nickname')->readonly() ?>
        <?= $form->field($model, 'avatar')->readonly(['value' => $model->avatar ?: '未设置']) ?>
        <?= $form->field($model, 'mobile')->readonly(['value' => $model->mobile ?: '未设置']) ?>
        <?= $form->field($model, 'email')->readonly(['value' => $model->email ?: '未设置']) ?>
        <?= $form->field($model, 'status')->readonly(['value' => $model->statusName]) ?>
        <?= $form->field($model, 'register_type')->readonly(['value' => $model->registerTypeName]) ?>
        <?= $form->field($model, 'last_login_time')->readonly(['value' => $model->last_login_time ?: '从未登录']) ?>
        <?= $form->fieldReadonly('可用数量', $account?->available_amount ?? '账户数据异常') ?>
        <?= $form->fieldReadonly('冻结数量', $account?->frozen_amount ?? '账户数据异常') ?>
        <?= $form->field($model, 'create_time')->readonly() ?>
        <?= $form->field($model, 'update_time')->readonly() ?>
    </div>
    <div class="card-footer bg-transparent">
        <div class="row g-2">
            <div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
                <?= Html::navigateButton(
                    '修改',
                    ['member/member/modify', 'id' => $model->id],
                    ['class' => 'btn btn-primary']
                ) ?>
                <?= Html::backButton() ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title h6 mb-0">社交账号</h3>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-bordered table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>平台</th>
                        <th>平台用户ID</th>
                        <th>平台昵称</th>
                        <th>绑定状态</th>
                        <th>最后登录时间</th>
                        <th>绑定时间</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($model->authIdentities === []) : ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">暂未绑定社交账号</td></tr>
                    <?php else : ?>
                        <?php foreach ($model->authIdentities as $identity) : ?>
                            <?php /** @var common\models\member\MemberAuthIdentity $identity 社交身份 */ ?>
                            <tr>
                                <td><?= Html::encode($identity->providerName) ?></td>
                                <td><?= Html::encode($identity->provider_user_id) ?></td>
                                <td><?= Html::encode($identity->nickname) ?></td>
                                <td><?= Html::encode($identity->statusName) ?></td>
                                <td><?= Html::encode($identity->last_login_time ?: '从未登录') ?></td>
                                <td><?= Html::encode($identity->create_time) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
