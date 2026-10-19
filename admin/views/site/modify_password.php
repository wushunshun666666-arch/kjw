<?php
/**
 * modify_password.php
 * @author suxh
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */
/* @var $model app\models\site\ModifyPasswordForm */

?>
<?php $form = ActiveForm::begin(['layout' => 'horizontal']); ?>
<div class="card">
	<div class="card-body">
		<?= $form->field($model, 'oldPassword')->passwordInput() ?>
		<?= $form->field($model, 'newPassword')->passwordInput() ?>
		<?= $form->field($model, 'confirmPassword')->passwordInput() ?>
	</div>
	<div class="card-footer">
		<div class="row mb-3">
			<div class="col-sm-2"></div>
			<div class="col-sm-6">
				<?= Html::button('确定', ['class'=>'btn btn-primary', 'id'=>'ok']) ?>
				<?= Html::cancelButton() ?>
			</div>
		</div>
	</div>
</div>
<?php ActiveForm::end(); ?>

<script type="text/javascript">
(function ($) {
	seajs.use(['admin.form', 'admin.ui'], function(form, ui) {
		$('#ok').on('click', function() {
			form.btnAjaxSubmit($(this), function(data) {
				if (data.success()) {
					ui.successBox(data.message(), function() {
						ui.redirect(data.data().url, true);
						ui.closeModal();
					});
				}
				else {
					ui.messageBox(data.message());
				}
			});
		});
	});
})(window.jQuery);
</script>