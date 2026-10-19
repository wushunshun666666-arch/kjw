<?php

/**
 * 客服公共快捷回复列表页面。
 *
 * @var app\models\kefu\QuickReplySearchForm $model 搜索表单
 * @var array $statusNames 状态字典
 */

use app\helpers\Html;
use app\widgets\DataTableActiveForm;

?>
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title h6 mb-0">快捷回复管理</h3>
    </div>
    <div class="card-body">
        <?php $form = DataTableActiveForm::begin(['dataTableId' => 'quick-reply-list']); ?>
        <?= $form->field($model, 'keyword')->textInput(['placeholder' => '标题或内容']) ?>
        <?= $form->field($model, 'status')->searchDropDownList($statusNames) ?>
        <?= $form->queryButton() ?>
        <?php DataTableActiveForm::end(); ?>
        <?= $form->table([
            'head' => [
                'ID' => ['width' => '8%'],
                '标题' => ['width' => '20%'],
                '内容' => [],
                '排序' => ['width' => '8%'],
                '状态' => ['width' => '10%'],
                '操作' => ['width' => '14%'],
            ],
            'url' => ['list'],
            'load' => true,
        ]) ?>
    </div>
</div>
