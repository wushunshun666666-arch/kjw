<?php
/** 标准开奖记录录入。@var common\models\lottery\DrawInput $model @var array $gameNames */
use app\helpers\Html;
use app\widgets\ActiveForm;
?>
<div class="card shadow-sm mb-3"><div class="card-header"><h1 class="card-title h6 mb-0">录入已核实的开奖记录</h1></div>
<?php $form = ActiveForm::begin(['layout' => 'horizontal', 'validationUrl' => ['check-add']]); ?>
<div class="card-body">
<div class="alert alert-info">历史开奖结果不会被覆盖。请核实期号、号码和北京时间后提交。</div>
<?= $form->field($model, 'game_id')->selectDropDownList($gameNames) ?>
<?= $form->field($model, 'issue')->textInput(['maxlength' => 64]) ?>
<?= $form->field($model, 'draw_time')->textInput(['placeholder' => '2026-10-18 12:00:00']) ?>
<?= $form->field($model, 'numbers')->textInput(['maxlength' => 255]) ?>
<?= $form->field($model, 'source')->textInput(['maxlength' => 128]) ?>
</div><div class="card-footer bg-transparent">
<?= Html::button('确认入库', ['type' => 'button', 'class' => 'btn btn-primary', 'id' => 'lottery-draw-submit']) ?> <?= Html::backButton() ?>
</div><?php ActiveForm::end(); ?></div>
<script>
(function ($) {
    'use strict';
    seajs.use(['admin.form', 'admin.ui'], function (form, ui) {
        $('#lottery-draw-submit').off('click.drawReceive').on('click.drawReceive', function () {
            form.btnAjaxSubmit($(this), function (result) {
                if (!result.success()) { ui.errorBox(result.message()); return; }
                ui.successBox(result.message(), function () { ui.navigate(result.data().url); });
            });
        });
    });
})(window.jQuery);
</script>
