<?php
/** 公告独立修改表单。@var app\models\portal\notice\ModifyForm $model @var array $enabledNames */
use app\helpers\Html;
use app\widgets\ActiveForm;
?>
<div class="card shadow-sm mb-3"><div class="card-header"><h1 class="card-title h6 mb-0">修改公告</h1></div>
<?php $form = ActiveForm::begin(['layout' => 'horizontal', 'validationUrl' => ['check-modify']]); ?>
<div class="card-body">
<?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'content')->textarea(['rows' => 8]) ?>
<?= $form->field($model, 'enabled')->selectDropDownList($enabledNames) ?>
<?= $form->field($model, 'version')->hiddenInput()->label(false) ?>
</div><div class="card-footer bg-transparent">
<?= Html::button('保存', ['type' => 'button', 'class' => 'btn btn-primary', 'id' => 'lottery-notice-modify-submit']) ?> <?= Html::backButton() ?>
</div><?php ActiveForm::end(); ?></div>
<script>
(function ($) {
    'use strict';
    seajs.use(['admin.form', 'admin.ui'], function (form, ui) {
        $('#lottery-notice-modify-submit').off('click.lotterySave').on('click.lotterySave', function () {
            form.btnAjaxSubmit($(this), function (result) {
                if (!result.success()) { ui.errorBox(result.message()); return; }
                ui.successBox(result.message(), function () { ui.navigate(result.data().url); });
            });
        });
    });
})(window.jQuery);
</script>
