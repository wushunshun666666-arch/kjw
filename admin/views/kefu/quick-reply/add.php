<?php

/**
 * @var app\models\kefu\QuickReplyForm $model 新增快捷回复表单
 * @var array $statusNames 快捷回复状态字典
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

?>
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title h6 mb-0">新增快捷回复</h3>
    </div>
    <?php $form = ActiveForm::begin(['layout' => 'horizontal']); ?>
    <div class="card-body">
        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'content')->textarea(['maxlength' => true, 'rows' => 6]) ?>
        <?= $form->field($model, 'orderby')->textInput(['type' => 'number']) ?>
        <?= $form->field($model, 'status')->selectDropDownList($statusNames) ?>
    </div>
    <div class="card-footer bg-transparent">
        <div class="row g-2">
            <div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
                <?= Html::button('新增', [
                    'class' => 'btn btn-primary',
                    'id' => 'quick-reply-add-submit',
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
        $('#quick-reply-add-submit')
            .off('click.quickReplyAdd')
            .on('click.quickReplyAdd', function () {
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
