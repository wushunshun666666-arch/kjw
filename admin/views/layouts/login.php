<?php
/**
 * login.php
 *
 * @author suxh
 */

use app\assets\AppAsset;
use app\helpers\Html;
use app\widgets\BrowserUpgradeTips;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);

$name = \Yii::$app->name;
$homeUrl = \Yii::$app->getHomeUrl();
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
    <title><?= Html::encode($this->title ?: $name . '登录') ?></title>
    <meta  name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
	<meta http-equiv="pragma" content="no-cache">
	<meta http-equiv="cache-control" content="no-cache">
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
</head>
<body class="login-page admin-login-page">
	<?php $this->beginBody() ?>
    <?= BrowserUpgradeTips::widget() ?>
    <main class="admin-login-shell">
        <section class="admin-login-card" aria-labelledby="admin-login-title">
            <div class="admin-login-brand-panel">
                <a class="admin-login-brand-link" href="<?= Html::encode($homeUrl) ?>">
                    <span class="admin-login-mark" aria-hidden="true">M</span>
                    <span>MANAGEMENT CONSOLE</span>
                </a>
                <div class="admin-login-brand-copy">
                    <span class="admin-login-eyebrow">ADMINLTE 4 · YII2</span>
                    <h1 id="admin-login-title"><?= Html::encode($name) ?></h1>
                    <p>安全、高效地管理您的业务数据</p>
                </div>
                <div class="admin-login-brand-status">
                    <i class="fa fa-shield" aria-hidden="true"></i>
                    <span>后台管理安全登录</span>
                </div>
            </div>
            <div class="admin-login-form-panel">
                <header class="admin-login-form-heading">
                    <h2>欢迎回来</h2>
                    <p>请输入账户信息登录后台</p>
                </header>
                <?= $content ?>
                <p class="admin-login-meta">AdminLTE 4 · Yii2</p>
            </div>
        </section>
    </main>
	<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
