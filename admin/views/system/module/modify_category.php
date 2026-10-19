<?php
/**
 * modify.php
 *
 * @author xu
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */
/* @var $model common\models\system\Module */
?>
<div class="card shadow-sm system-card mb-3">
	<?php $form = ActiveForm::begin(['layout' => 'horizontal','validationUrl'=>['system/module/check-modify-category']]); ?>
	<div class="card-body">
		<?= Html::activeHiddenInput($model, 'parent_id')?>
		<?= $form->field($model, 'name',['enableAjaxValidation' => true])->textInput() ?>
	</div>
	<div class="card-footer bg-transparent">
		<div class="row g-2">
			<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
				<?= Html::button('修改', ['class' => 'btn btn-primary', 'id'=>'modify']) ?>
				<?= Html::cancelButton() ?>
			</div>
		</div>
	</div>
	<?php ActiveForm::end(); ?>
</div>
<script type="text/javascript">
(function ($) {
	seajs.use(['admin.form', 'admin.ui'], function(form, ui) {
		$('#modify').click(function() {
			form.btnAjaxSubmit($(this), function(data) {
				if (data.success()) {
					ui.successBox(data.message(), function() {
						ui.navigate(data.data().url);
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
