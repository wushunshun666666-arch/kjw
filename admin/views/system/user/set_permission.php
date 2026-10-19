<?php
/**
 * set_permission.php
 */

use app\helpers\Html;
use app\widgets\ActiveForm;
use app\widgets\MultiSelect2Side;
?>
<?php $form = ActiveForm::begin(['layout' => 'horizontal']); ?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header"><h3 class="card-title h6 mb-0">设置用户权限</h3></div>
	<div class="card-body">
		<?= $form->field($model, 'id')->readonly() ?>
		<?= $form->field($model, 'username')->readonly() ?>
		<?= $form->field($model, 'name')->readonly() ?>
		<?= Html::hiddenInput('submit', '1') ?>
	</div>
</div>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header">
		<h3 class="card-title h6 mb-0">角色权限</h3>
	</div>
	<div class="card-body">
		<div class="row g-2">
			<div class="offset-sm-3 col-sm-8">
			<?= MultiSelect2Side::widget([
				'name' => 'roles[]',
				'data' => $roles,
				'selectableTitle' => '可选角色',
				'selectionTitle' => '已选角色',
			]) ?>
			</div>
		</div>
	</div>
	<div class="card-footer bg-transparent">
		<div class="row g-2">
			<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
				<?= Html::button('设置', ['class' => 'btn btn-primary', 'id'=>'ok']) ?>
				<?= Html::backButton() ?>
			</div>
		</div>
	</div>
</div>
<?php ActiveForm::end(); ?>
<script type="text/javascript">
(function ($) {
	seajs.use(['admin.form', 'admin.ui'], function(form, ui) {
		$('#ok').click(function() {
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
