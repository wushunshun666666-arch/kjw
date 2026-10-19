<?php
/**
 * set_permission.php
 */
use app\helpers\Html;
use app\widgets\ActiveForm;
use app\widgets\CheckBoxTreeView;
?>
<?php $form = ActiveForm::begin(['layout' => 'horizontal']); ?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header"><h3 class="card-title h6 mb-0">查看角色权限</h3></div>
	<div class="card-body">
		<?= $form->field($model, 'name')->readonly() ?>
	</div>
</div>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header">
		<h3 class="card-title h6 mb-0">模块权限</h3>
	</div>
	<div class="card-body">
	<?= CheckBoxTreeView::widget([
		'data' => $moduleTree,
		'autoOpen' => true,
		'disabled' => true,
	]) ?>
	</div>
	<div class="card-footer bg-transparent">
		<div class="row g-2">
			<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
				<?= Html::backButton(['backurl'=>$backurl]) ?>
			</div>
		</div>
	</div>
</div>
<?php ActiveForm::end(); ?>