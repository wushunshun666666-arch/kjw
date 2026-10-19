<?php
use app\helpers\Html;
use app\widgets\ActiveForm;
use yii\captcha\Captcha;

/* @var $this yii\web\View */
/* @var $form yii\bootstrap5\ActiveForm */
/* @var $model \app\models\site\LoginForm */
$showCaptcha = $model->isNeedCaptcha()? '':'display:none';
?>
<div class="alert alert-warning alert-login" role="alert" aria-live="polite">
	<div class="alert-login-content">
		<i class="icon fa fa-warning" aria-hidden="true"></i>
		<span class="alert-login-message"></span>
	</div>
	<button type="button" class="btn-close alert-login-close" aria-label="关闭"></button>
</div>
<?php $form = ActiveForm::begin(['options'=>['onsubmit'=>'return false;']]); ?>
	<div class="input-group login-input-group mb-3">
		<span class="input-group-text"><i class="fa fa-user" aria-hidden="true"></i></span>
		<?= Html::activeTextInput($model, 'username', ['class'=>'form-control', 'placeholder'=>'请输入用户名']) ?>
	</div>
	<div class="input-group login-input-group mb-3">
		<span class="input-group-text"><i class="fa fa-lock" aria-hidden="true"></i></span>
		<?= Html::activePasswordInput($model, 'password', ['class'=>'form-control', 'placeholder'=>'请输入密码']) ?>
	</div>
	<div class="mb-3 form-group-captcha" style="<?=$showCaptcha ?>">
		<?= Captcha::widget([
			'model' => $model,
			'attribute' => 'captcha',
			'template' => '{input} {image}',
			'options' => ['class'=>'form-control', 'placeholder'=>'请输入验证码'],
			'imageOptions' => ['title' => '点击更换验证码'],
		]) ?>
	</div>
	<div class="d-grid">
		<?= Html::submitButton('<span>登录</span><i class="fa fa-arrow-right" aria-hidden="true"></i>', ['class' => 'btn btn-primary login-submit-btn', 'id' => 'login-btn'])?>
	</div>
<?php ActiveForm::end(); ?>

<script type="text/javascript">
(function ($) {
	function showError(message) {
		var $alert = $('.alert-login');
		$alert.find('.alert-login-message').text(message);
		$alert.stop(true, true).fadeIn(150);
	}

	$('.alert-login-close').on('click', function () {
		$('.alert-login').fadeOut(120);
	});

	seajs.use(['admin.form', 'admin.ui'], function(form, ui) {
		$('#login-btn').on('click', function() {
			form.btnAjaxSubmit($(this), function(data) {
				if (data.success()) {
					ui.redirect(data.data().url, true);
				}
				else {
					showError(data.message());
					if (data.data().outOfLimit) {
						$('.form-group-captcha').css('display', 'grid');
					}
					$('#loginform-captcha').val('');
					$('#loginform-captcha-image').yiiCaptcha('refresh');
				}
			}, {showLoading:false});

			return false;
		});
	});
})(window.jQuery);
</script>
