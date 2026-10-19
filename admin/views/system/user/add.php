<?php
/**
 * add.php
 *
 * @author suxh
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */
/* @var $model app\models\system\UserAddForm */
?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header"><h3 class="card-title h6 mb-0">新增用户</h3></div>
	<?php $form = ActiveForm::begin(['layout' => 'horizontal', 'validationUrl'=>['system/user/check-add']]); ?>
	<div class="card-body">
		<?= $form->field($model, 'username', ['enableAjaxValidation' => true])->textInput() ?>
		<?= $form->field($model, 'name')->textInput() ?>
		<?= $form->field($model, 'department_id')->selectDropDownList($departments) ?>
		<?= $form->field($model, 'tel')->textInput(['inputmode' => 'tel']) ?>
		<?= $form->field($model, 'mobi')->textInput(['inputmode' => 'tel']) ?>
		<?= $form->field($model, 'email')->textInput(['type' => 'email']) ?>
		<?= $form->field($model, 'note')->textarea(['rows' => 3]) ?>
	</div>
	<div class="card-footer bg-transparent">
		<div class="row g-2">
			<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
				<?= Html::button('新增', ['class' => 'btn btn-primary', 'id'=>'add']) ?>
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
