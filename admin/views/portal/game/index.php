<?php
/** 彩种列表。@var app\models\portal\game\SearchForm $model @var array $enabledNames */
use app\widgets\DataTableActiveForm;
?>
<?= \app\widgets\TabMenu::widget(['menus' => ['彩种管理' => ['/portal/game/index'], '开奖记录' => ['/portal/draw/index'], '公告管理' => ['/portal/notice/index'], '新增彩种' => ['add']], 'active' => '彩种管理']) ?>
<div class="card shadow-sm mb-3"><div class="card-header"><h1 class="card-title h6 mb-0">彩种管理</h1></div><div class="card-body">
<?php $form = DataTableActiveForm::begin(['dataTableId' => 'lottery-game-list']); ?>
<?= $form->field($model, 'name')->textInput() ?>
<?= $form->field($model, 'enabled')->searchDropDownList($enabledNames) ?>
<?= $form->queryButton() ?>
<?php DataTableActiveForm::end(); ?>
<?= $form->table(['head' => ['ID' => [], '名称' => [], '发布状态' => [], '修改时间' => [], '操作' => []], 'url' => ['list'], 'load' => true]) ?>
</div></div>
