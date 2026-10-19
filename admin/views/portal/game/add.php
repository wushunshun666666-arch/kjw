<?php
/** 彩种独立新增表单。@var app\models\portal\game\AddForm $model @var array $enabledNames */
use app\helpers\Html;
use app\widgets\ActiveForm;
?>
<div class="card shadow-sm mb-3"><div class="card-header"><h1 class="card-title h6 mb-0">新增彩种</h1></div>
<?php $form = ActiveForm::begin(['layout' => 'horizontal', 'validationUrl' => ['check-add']]); ?>
<div class="card-body">
<?= $form->field($model, 'code')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'description')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'ball_count')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'ball_min')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'ball_max')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'sort_order')->textInput(['maxlength' => true]) ?>
<?= $form->field($model, 'enabled')->selectDropDownList($enabledNames) ?>
</div><div class="card-footer bg-transparent">
<?= Html::button('保存', ['type' => 'button', 'class' => 'btn btn-primary', 'id' => 'lottery-game-add-submit']) ?> <?= Html::backButton() ?>
</div><?php ActiveForm::end(); ?></div>
<script>
(function ($) {
    'use strict';
    seajs.use(['admin.form', 'admin.ui'], function (form, ui) {
        $('#lottery-game-add-submit').off('click.lotterySave').on('click.lotterySave', function () {
            form.btnAjaxSubmit($(this), function (result) {
                if (!result.success()) { ui.errorBox(result.message()); return; }
                ui.successBox(result.message(), function () { ui.navigate(result.data().url); });
            });
        });
    });
})(window.jQuery);
</script>
