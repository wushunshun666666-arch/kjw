<?php
/**
 * success.php
 *
 * @author suxh
 */

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $message string */
/* @var $exception Exception */

?>
<div class="card">
    <div class="card-body">

        <div class="alert alert-success alert-dismissible">
            <h4>
                <i class="icon fa fa-check"></i>
                操作成功！
            </h4>

            <?= nl2br(Html::encode($message)) ?>
        </div>

    </div>
</div>