<?php

use app\helpers\Html;
use app\widgets\DataTableActiveForm;

/* @var $model app\models\system\AllowLoginIpSearchForm */
?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header d-flex align-items-center justify-content-between">
		<h3 class="card-title h6 mb-0">可登录IP列表</h3>
		<?= Html::navigate('新增可登录IP', ['system/allow-login-ip/add'], ['class' => 'btn btn-primary btn-sm']) ?>
	</div>
	<div class="card-body">
		<?php $form = DataTableActiveForm::begin(['dataTableId' => 'allow-login-ip-list']); ?>
			<?= $form->field($model, 'ip')->textInput() ?>
			<?= $form->field($model, 'ownership')->textInput() ?>
			<?= $form->queryButton() ?>
		<?php DataTableActiveForm::end(); ?>

		<?= $form->table([
			'head' => [
				'IP' => ['width' => '18%'],
				'IP归属' => ['width' => '20%'],
				'备注' => ['width' => '24%'],
				'添加时间' => ['width' => '14%'],
				'更新时间' => ['width' => '14%'],
				'操作' => ['width' => '10%'],
			],
			'url' => ['list'],
			'load' => true,
		]) ?>
	</div>
</div>
