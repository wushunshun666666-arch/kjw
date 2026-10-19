<?php
/** 开奖记录查询。@var app\models\portal\draw\SearchForm $model @var array $gameNames @var array $sources */
use app\widgets\DataTableActiveForm;
use app\widgets\TabMenu;
use app\helpers\Html;
use yii\web\JsExpression;
?>
<?= TabMenu::widget(['menus' => ['彩种管理' => ['/portal/game/index'], '开奖记录' => ['index'], '公告管理' => ['/portal/notice/index'], '录入开奖' => ['add']], 'active' => '开奖记录']) ?>
<div class="card shadow-sm mb-3"><div class="card-header"><h1 class="card-title h6 mb-0">开奖记录</h1></div><div class="card-body">
<p class="text-muted">采集入口读取开奖接口并写入数据库；前台的开奖结果、走势和统计均读取已入库记录。重复期号不会重复入库，同一期号的冲突结果会拒绝覆盖。</p>
<details class="mb-3" open><summary>各彩种采集状态</summary>
<div class="table-responsive"><table class="table table-bordered align-middle mt-2"><thead><tr><th>彩种</th><th>最新开奖</th><th>最近采集</th><th>最近成功</th><th>状态</th><th>操作</th></tr></thead><tbody>
<?php foreach ($sources as $source): ?>
<tr><td><span title="<?= Html::encode($source['url']) ?>"><?= Html::encode($source['name']) ?></span></td><td><?= Html::encode($source['latest']) ?></td><td><?= Html::encode($source['attempt']) ?></td><td><?= Html::encode($source['success']) ?></td><td><?= Html::encode($source['status']) ?></td><td><?= Html::confirmA('立即采集' . Html::encode($source['name']), ['portal/draw/collect', 'CollectForm[game]' => $source['code']], '从此彩种的固定采集地址读取开奖并入库？重复记录不会新增。', ['class' => 'btn btn-primary btn-sm', 'title' => '采集开奖', 'success' => new JsExpression('function () { window.location.reload(); }')]) ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></details>
<?php $form = DataTableActiveForm::begin(['dataTableId' => 'lottery-draw-list']); ?>
<?= $form->field($model, 'game_id')->searchDropDownList($gameNames) ?>
<?= $form->field($model, 'issue')->textInput() ?>
<?= $form->queryButton() ?>
<?php DataTableActiveForm::end(); ?>
<?= $form->table(['head' => ['彩种' => [], '期号' => [], '开奖时间' => [], '号码' => [], '来源' => [], '接收时间' => []], 'url' => ['list'], 'load' => true]) ?>
</div></div>
