<?php

/* @var $model app\models\system\AllowLoginIpModifyForm */
?>
<div class="card shadow-sm system-card mb-3">
	<div class="card-header"><h3 class="card-title h6 mb-0">修改可登录IP</h3></div>
	<?= $this->render('_form', [
		'model' => $model,
		'validationUrl' => ['system/allow-login-ip/check-modify'],
		'buttonId' => 'modify',
		'buttonLabel' => '修改',
	]) ?>
</div>
