<?php
/**
 * profile.php
 * @author suxh
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */
/* @var $model app\models\site\ProfileForm */

$modifyPasswordUrl = ['site/modify-password'];
?>
<div class="card card-primary card-outline">
	<div class="card-header"><h3 class="card-title">我的信息</h3></div>
	<?php $form = ActiveForm::begin(['layout' => 'horizontal']); ?>
	<div class="card-body">
		<?= $form->field($model, 'id')->readonly() ?>
		<?= $form->field($model, 'username')->readonly() ?>
		<?= $form->field($model, 'name')->readonly() ?>
		<?= $form->field($model, 'department')->readonly() ?>
		<?= $form->field($model, 'tel')->textInput() ?>
		<?= $form->field($model, 'mobi')->textInput() ?>
		<?= $form->field($model, 'email')->textInput() ?>
		<?= $form->field($model, 'note')->textInput() ?>
	</div>
	<div class="card-footer">
		<div class="row mb-3">
			<div class="col-sm-2"></div>
			<div class="col-sm-6">
				<?= Html::button('修改', ['class'=>'btn btn-primary', 'id'=>'modify']) ?>
				<?= Html::modalButton('修改密码', $modifyPasswordUrl, [
					'class' => 'btn btn-primary',
				]) ?>
			</div>
		</div>
	</div>
	<?php ActiveForm::end(); ?>
</div>

<script type="text/javascript">
(function ($) {
	seajs.use(['admin.form', 'admin.ui'], function(form, ui) {
		$('#modify').on('click', function() {
			form.btnAjaxSubmit($(this), function(data) {
				if (data.success()) {
					ui.successBox(data.message());
				}
				else {
					ui.messageBox(data.message());
				}
			});
		});
	});
})(window.jQuery);
</script>