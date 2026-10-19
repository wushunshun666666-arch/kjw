<?php

/**
 * 后台会员列表页面。
 *
 * @var app\models\member\SearchForm $model 会员搜索表单
 * @var array $statusNames 会员状态字典
 * @var array $registerTypeNames 注册方式字典
 */

use app\helpers\Html;
use app\widgets\DataTableActiveForm;

?>
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title h6 mb-0">会员列表</h3>
    </div>
    <div class="card-body">
        <?php $form = DataTableActiveForm::begin(['dataTableId' => 'member-list']); ?>
            <?= $form->field($model, 'id')->textInput(['size' => 6]) ?>
            <?= $form->field($model, 'username')->textInput(['size' => 10]) ?>
            <?= $form->field($model, 'nickname')->textInput(['size' => 10]) ?>
            <?= $form->field($model, 'mobile')->textInput(['size' => 12]) ?>
            <?= $form->field($model, 'email')->textInput(['size' => 14]) ?>
            <?= $form->field($model, 'status')->searchDropDownList($statusNames) ?>
            <?= $form->field($model, 'registerType')->searchDropDownList($registerTypeNames) ?>
            <?= $form->queryButton() ?>
        <?php DataTableActiveForm::end(); ?>

        <?= $form->table([
            'head' => [
                '会员ID' => ['width' => '7%'],
                '用户名/昵称' => ['width' => '15%'],
                '联系方式' => ['width' => '16%'],
                '可用数量' => ['width' => '10%'],
                '冻结数量' => ['width' => '10%'],
                '注册方式' => ['width' => '9%'],
                '状态' => ['width' => '8%'],
                '创建时间' => ['width' => '13%'],
                '操作' => ['width' => '12%'],
            ],
            'url' => ['list'],
            'load' => true,
        ]) ?>
    </div>
</div>
