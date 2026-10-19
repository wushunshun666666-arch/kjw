<?php
/**
 * detail.php
 *
 * @author xurs
 */

use app\helpers\Html;
use app\widgets\ActiveForm;
use app\widgets\DataTableView;

/* @var $this yii\web\View */
/* @var $form app\widgets\ActiveForm */

?>

<?php $form = ActiveForm::begin(['layout' => 'horizontal']); ?>
<div class="card shadow-sm system-card mb-3">
		<div class="card-header"><h3 class="card-title h6 mb-0">模块详细信息</h3></div>
		<div class="card-body">
			<?= $form->field($model, 'id')->readonly() ?>
			<?= $form->field($model, 'name')->readonly() ?>
			<?= $form->field($model, 'url')->readonly() ?>
			<?= $form->field($model, 'description')->readonly() ?>
			<?= $form->field($model, 'create_time')->readonly() ?>
			<?= $form->field($model, 'update_time')->readonly() ?>
		</div>
	</div>


	<div class="card shadow-sm system-card mb-3">
		<div class="card-header"><h3 class="card-title h6 mb-0">模块角色</h3></div>
		<div class="card-body">
			<?= DataTableView::widget([
			'id' => 'role-module-list',
			'head' => [
				'角色ID'		=> ['width'=>'10%'],
				'角色名称'	=> ['width'=>'10%'],
				'添加时间'	=> ['width'=>'10%'],
			 
			],
			'url' => ['role-module', 'id'=>$model->id],
			'load' => true,
		]) ?>
		</div>
		<div class="card-footer bg-transparent">
			<div class="row g-2">
				<div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
					<?= Html::backButton() ?>
				</div>
			</div>
		</div>
	</div>
<?php ActiveForm::end(); ?>
