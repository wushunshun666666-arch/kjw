<?php

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $model app\models\system\AllowLoginIpForm */
/* @var $validationUrl array */
/* @var $buttonId string */
/* @var $buttonLabel string */
?>
<?php $form = ActiveForm::begin(['layout' => 'horizontal', 'validationUrl' => $validationUrl]); ?>
<div class="card-body">
	<?php if (property_exists($model, 'id')): ?>
		<?= Html::activeHiddenInput($model, 'id') ?>
	<?php endif; ?>
	<?= $form->field($model, 'ip', ['enableAjaxValidation' => true])->textInput(['placeholder' => '如：192.168.1.*']) ?>
	<?= $form->field($model, 'ownership')->textInput() ?>
	<?= $form->field($model, 'note')->textInput() ?>
</div>
<div class="card-footer bg-transparent">
	<div class="row g-2">
		<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
			<?= Html::button($buttonLabel, ['class' => 'btn btn-primary', 'id' => $buttonId]) ?>
			<?= Html::backButton() ?>
		</div>
	</div>
</div>
<?php ActiveForm::end(); ?>

<script type="text/javascript">
(function ($) {
	seajs.use(['admin.form', 'admin.ui'], function(form, ui) {
		$('#<?= $buttonId ?>').click(function() {
			form.btnAjaxSubmit($(this), function(data) {
				if (data.success()) {
					ui.successBox(data.message(), function() { ui.navigate(data.data().url); });
				} else {
					ui.messageBox(data.message());
				}
			});
		});
	});
})(window.jQuery);
</script>
