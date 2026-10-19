<?php
/**
 * modal.php
 *
 * @author suxh
 */

use app\assets\AppAsset;
use app\helpers\Html;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?= Html::encode($this->title) ?></title>
	<meta  name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
</head>
<body class="modal-main-content">
    <?php $this->beginBody() ?>
	<?= $content ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>