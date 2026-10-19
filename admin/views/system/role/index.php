<?php
/**
 * index.php
 *
 * @author suxh
 */

use app\widgets\DataTableActiveForm;

/* @var $this yii\web\View */
/* @var $form app\widgets\DataTableActiveForm */
/* @var $model app\models\system\RoleSearchForm */
?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header">
		<h3 class="card-title h6 mb-0">角色列表</h3>
	</div>
	<div class="card-body">
		<div class="row search-form">
			<div class="col-12">
			<?php $form = DataTableActiveForm::begin(['dataTableId'=>'role-list']); ?>
				<?= $form->field($model, 'name')->textInput() ?>
				<?= $form->queryButton() ?>
			<?php DataTableActiveForm::end(); ?>
			</div>
		</div>
		<div class="row">
			<div class="col-12">
			<?= $form->table([
				'head' => [
					'角色ID'	=> ['width'=>'10%'],
					'角色名称'	=> ['width'=>'15%'],
					'角色描述'	=> ['width'=>'20%'],
					'添加时间'	=> ['width'=>'15%'],
					'更新时间'	=> ['width'=>'15%'],
					'操作'		=> ['width'=>'25%'],
				],
				'url' => ['list'],
				'load' => true,
			]) ?>
			</div>
		</div>
	</div>
</div>
