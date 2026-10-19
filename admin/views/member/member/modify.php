<?php

/**
 * 后台修改会员页面。
 *
 * @var app\models\member\ModifyForm $model 修改会员表单
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

?>
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title h6 mb-0">修改会员</h3>
    </div>
    <?php $form = ActiveForm::begin([
        'layout' => 'horizontal',
        'validationUrl' => ['member/member/check-modify'],
    ]); ?>
    <div class="card-body">
        <?= Html::activeHiddenInput($model, 'id') ?>
        <?= $form->field($model, 'id')->readonly() ?>
        <?= $form->field($model, 'username')->readonly() ?>
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
                <?= Html::button('修改', [
                    'class' => 'btn btn-primary',
                    'id' => 'member-modify-submit',
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
        $('#member-modify-submit')
            .off('click.memberModify')
            .on('click.memberModify', function () {
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
