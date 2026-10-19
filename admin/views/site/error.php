<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

$this->title = $name;
?>
<div class="card">
    <div class="card-body">

        <div class="alert alert-warning alert-dismissible">
            <h4>
                <i class="icon fa fa-warning"></i>
                操作失败！
            </h4>

            <?= nl2br(Html::encode($message)) ?>
        </div>

    </div>
</div>
