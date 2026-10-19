<?php
/** 历史与和值走势。@var frontend\models\HistoryForm $filter @var bool $trend @var bool $installed @var array $games @var common\models\lottery\Game|null $selected @var array $rows @var int $total @var int $pages @var array $columns */
use yii\helpers\Html;
use yii\helpers\Url;
$this->title = ($trend ? '号码走势' : '历史开奖') . ' · 开奖数据中心';
$route = $trend ? 'site/trend' : 'site/history';
?>
<div class="page-heading"><span class="eyebrow"><?= $trend ? 'NUMBER TRENDS' : 'DRAW ARCHIVE' ?></span><h1><?= $trend ? '号码走势' : '历史开奖' ?></h1><p><?= $trend ? '按期查看真实开奖记录的和值分布。' : '每一期结果，都可以回头查阅。' ?></p></div>
<div class="archive-panel"><div class="game-tabs" aria-label="切换彩种"><?php foreach ($games as $game): ?><a class="<?= $selected?->id === $game->id ? 'active' : '' ?>" href="<?= Url::to([$route, 'game' => $game->code]) ?>"><?= Html::encode($game->name) ?></a><?php endforeach; ?></div>
<form class="filter-form" method="get" action="<?= Url::to([$route]) ?>">
<input type="hidden" name="r" value="<?= Html::encode($route) ?>"><input type="hidden" name="game" value="<?= Html::encode($selected?->code ?? '') ?>">
<label>开奖日期<input type="date" name="date" value="<?= Html::encode($filter->date) ?>"></label>
<label>开奖期号<input name="issue" maxlength="64" placeholder="输入完整期号" value="<?= Html::encode($filter->issue) ?>"></label>
<button class="button button-primary" type="submit">查询记录</button><a class="reset-link" href="<?= Url::to([$route, 'game' => $selected?->code]) ?>">重置</a></form>
<div class="archive-summary"><h2><?= Html::encode($selected?->name ?? '开奖记录') ?></h2><span>共 <?= Html::encode($total) ?> 条记录 · 北京时间</span></div>
<?php if (!$rows): ?><div class="empty-state"><span class="empty-symbol">▤</span><h3><?= !$installed || !$selected ? '等待配置彩种与开奖数据' : '没有符合条件的开奖记录' ?></h3><p>请在数据接入后调整日期或期号查询。</p></div>
<?php else: ?><div class="table-scroll"><table class="history-table"><thead><tr><th>开奖期号</th><th>开奖时间</th><?php if ($trend && $columns): foreach ($columns as $column): ?><th class="trend-column"><?= Html::encode($column) ?></th><?php endforeach; else: ?><th>开奖号码</th><?php endif; ?><th>和值</th><th>数据来源</th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td class="issue-cell"><?= Html::encode($row['issue']) ?></td><td class="time-cell"><?= Html::encode($row['time']) ?></td>
<?php if ($trend && $columns): foreach ($columns as $column): ?><td class="trend-cell"><?= $row['total'] === $column ? '<span class="trend-dot">' . Html::encode($column) . '</span>' : '<span class="trend-guide">·</span>' ?></td><?php endforeach; else: ?><td><div class="table-balls"><?php foreach ($row['balls'] as $ball): ?><span class="number-ball small-ball"><?= Html::encode($ball) ?></span><?php endforeach; ?></div></td><?php endif; ?>
<td><strong><?= Html::encode($row['total']) ?></strong></td><td><?= Html::encode($row['source']) ?></td></tr><?php endforeach; ?></tbody></table></div>
<?php if ($trend && !$columns): ?><p class="muted">该彩种和值跨度较大，以逐期和值列表展示。</p><?php endif; ?>
<nav class="pagination" aria-label="历史记录分页"><?php if ($filter->page > 1): ?><a href="<?= Url::to([$route, 'game' => $selected?->code, 'date' => $filter->date, 'issue' => $filter->issue, 'page' => $filter->page - 1]) ?>">← 上一页</a><?php endif; ?><span>第 <?= Html::encode($filter->page) ?> / <?= Html::encode(max(1, $pages)) ?> 页</span><?php if ($filter->page < $pages): ?><a href="<?= Url::to([$route, 'game' => $selected?->code, 'date' => $filter->date, 'issue' => $filter->issue, 'page' => $filter->page + 1]) ?>">下一页 →</a><?php endif; ?></nav>
<?php endif; ?></div><p class="page-footnote">开奖结果以原始开奖机构公布为准。历史数据与走势仅作信息展示。</p>
