<?php
/**
 * detail.php
 * @author suxh
 */

use app\helpers\Html;
use app\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */
/* @var $model common\models\system\User */
?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header"><h3 class="card-title h6 mb-0">查看用户详细信息</h3></div>
	<?php $form = ActiveForm::begin(['layout' => 'horizontal']); ?>
	<div class="card-body">
		<?= $form->field($model, 'id')->readonly() ?>
		<?= $form->field($model, 'username')->readonly() ?>
		<?= $form->field($model, 'name')->readonly() ?>
		<?= $form->field($model, 'tel')->readonly() ?>
		<?= $form->field($model, 'mobi')->readonly() ?>
		<?= $form->field($model, 'email')->readonly() ?>
		<?= $form->field($model, 'create_time')->readonly() ?>
		<?= $form->field($model, 'update_time')->readonly() ?>
	</div>
	<div class="card-footer bg-transparent">
		<div class="row g-2">
			<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
				<?= Html::backButton() ?>
			</div>
		</div>
	</div>
	<?php ActiveForm::end(); ?>
</div>
