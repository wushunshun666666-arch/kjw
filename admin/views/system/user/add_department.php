<?php
/**
 * add.php
 * @author suxh
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */
?>


	<?php $form = ActiveForm::begin(['layout' => 'horizontal','validationUrl'=>['system/user/check-name']]); ?>
<div class="card shadow-sm system-card mb-3">
    	<div class="card-body">
    		        <?= $form->field($model, 'departmentname')->readonly(['value'=>$departmentname]) ?>
    				<?= $form->field($model, 'name', ['enableAjaxValidation' => true])->textInput() ?>
    			    <?= Html::activeHiddenInput($model, 'id') ?>
    	</div>
    	<div class="card-footer bg-transparent">
			<div class="row g-2">
				<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
					<?= Html::button('新增', ['class' => 'btn btn-primary', 'id'=>'add']) ?>
    				<?= Html::cancelButton() ?>
    			</div>
    		</div>
    	</div>
	</div>
	<?php ActiveForm::end(); ?>

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
