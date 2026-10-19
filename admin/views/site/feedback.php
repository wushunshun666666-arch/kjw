<?php
/**
 * feedback.php
 *
 * @author suxh
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */
/* @var $model app\models\system\FeedbackForm */
?>
<div class="card card-primary card-outline">
	<div class="card-header">
		<h3 class="card-title">反馈问题</h3>
	</div>
	<?php $form = ActiveForm::begin(['layout' => 'horizontal', 'id' => 'feedback-form']); ?>
	<div class="card-body">
		<input style="display:none" />
		<?= $form->field($model, 'title')->textInput() ?>
		<?= $form->field($model, 'content')->textarea(['rows'=>8]) ?>
	</div>
	<div class="card-footer">
		<div class="row mb-3">
			<div class="col-sm-2"></div>
			<div class="col-sm-6">
				<?= Html::button('提交', ['class'=>'btn btn-primary', 'id'=>'ok']) ?>
			</div>
		</div>
	</div>
	<?php ActiveForm::end(); ?>
</div>

<script type="text/javascript">
(function ($) {
	seajs.use(['admin.form', 'admin.ui'], function(form, ui) {
		$('#ok').on('click', function() {
			form.btnAjaxSubmit($(this), function(data) {
				if (data.success()) {
					ui.successBox(data.message(), function() {
						$('#feedback-form')[0].reset();
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