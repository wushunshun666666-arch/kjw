<?php

/* @var $model app\models\system\AllowLoginIpAddForm */
?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header"><h3 class="card-title h6 mb-0">新增可登录IP</h3></div>
	<?= $this->render('_form', [
		'model' => $model,
		'validationUrl' => ['system/allow-login-ip/check-add'],
		'buttonId' => 'add',
		'buttonLabel' => '新增',
	]) ?>
</div>
