<?php
/**
 * add.php
 *
 * @author xurs
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */
/* @var $model app\models\system\UserAddForm */
?>
<div class="card shadow-sm system-card mb-3">
	<?php $form = ActiveForm::begin(['layout' => 'horizontal', 'validationUrl'=>['system/module/check-url']]); ?>
	<div class="card-body">
		<?= $form->field($model, 'categoryId')->readonly(['value' => $categoryName]) ?>
		<?= $form->field($model, 'name')->textInput() ?>
		<?= $form->field($model, 'url', ['enableAjaxValidation' => true])->textInput() ?>
		<?= $form->field($model, 'description')->textarea(['rows' => 3]) ?>
	</div>
	<div class="card-footer bg-transparent">
		<div class="row g-2">
			<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
				<?= Html::button('新增', ['class' => 'btn btn-primary', 'id'=>'add']) ?>
				<?= Html::cancelButton() ?>
			</div>
		</div>
	</div>
	<?php ActiveForm::end(); ?>
</div>
<script type="text/javascript">
(function ($) {
	seajs.use(['admin.form', 'admin.ui'], function(form, ui) {
		$('#add').click(function() {
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
