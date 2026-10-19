<?php

/**
 * 后台新增会员页面。
 *
 * @var app\models\member\AddForm $model 新增会员表单
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

?>
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title h6 mb-0">新增会员</h3>
    </div>
    <?php $form = ActiveForm::begin([
        'layout' => 'horizontal',
        'validationUrl' => ['member/member/check-add'],
    ]); ?>
    <div class="card-body">
        <?= $form->field($model, 'username', ['enableAjaxValidation' => true])->textInput([
            'maxlength' => true,
            'autocomplete' => 'off',
        ]) ?>
        <?= $form->field($model, 'password')->passwordInput([
            'maxlength' => true,
            'autocomplete' => 'new-password',
        ]) ?>
        <?= $form->field($model, 'passwordConfirm')->passwordInput([
            'maxlength' => true,
            'autocomplete' => 'new-password',
        ]) ?>
        <?= $form->field($model, 'parentInviteCode')->textInput([
            'maxlength' => true,
            'autocomplete' => 'off',
            'class' => 'form-control text-uppercase',
        ]) ?>
        <?= $form->field($model, 'nickname')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'avatar')->textInput(['maxlength' => true, 'type' => 'url']) ?>
        <?= $form->field($model, 'mobile', ['enableAjaxValidation' => true])->textInput([
            'maxlength' => true,
            'inputmode' => 'tel',
        ]) ?>
        <?= $form->field($model, 'email', ['enableAjaxValidation' => true])->textInput([
            'maxlength' => true,
            'type' => 'email',
        ]) ?>
    </div>
    <div class="card-footer bg-transparent">
        <div class="row g-2">
            <div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
                <?= Html::button('新增', [
                    'class' => 'btn btn-primary',
                    'id' => 'member-add-submit',
                    'type' => 'button',
                ]) ?>
                <?= Html::backButton() ?>
            </div>
        </div>
    </div>
    <?php ActiveForm::end(); ?>
</div>

<script>
(function ($) {
    'use strict';

    seajs.use(['admin.form', 'admin.ui'], function (form, ui) {
        $('#member-add-submit')
            .off('click.memberAdd')
            .on('click.memberAdd', function () {
                form.btnAjaxSubmit($(this), function (result) {
                    if (!result.success()) {
                        ui.errorBox(result.message());
                        return;
                    }
                    ui.successBox(result.message(), function () {
                        ui.navigate(result.data().url);
                    });
                });
            });
    });
})(window.jQuery);
</script>
