<?php
/**
 * index.php
 *
 * @author suxh
 */
use app\widgets\ActiveForm;
use app\widgets\DataTableView;

/* @var $this yii\web\View */
/* @var $form app\widgets\DataTableActiveForm */
/* @var $model app\models\system\UserSearchForm */
?>
<?php $form = ActiveForm::begin(['layout' => 'horizontal']); ?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header"><h3 class="card-title h6 mb-0">查看角色用户</h3></div>
	<div class="card-body">
		 <?= $form->field($rolename, 'name')->readonly() ?>
	</div>
</div>
<?php ActiveForm::end(); ?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-body">
			<?= DataTableView::widget([
				'head' => [
					'用户ID'		=> ['width'=>'10%'],
					'用户名'		=> ['width'=>'10%'],
					'姓名'		=> ['width'=>'10%'],
					'手机号'		=> ['width'=>'10%'],
					'添加时间'	=> ['width'=>'15%'],
					'操作'		=> ['width'=>'15%'],
				],
				'url' => ['user-list', 'id'=>$rolename->id],
				'load' => true,
			]) ?>
	</div>
</div>
