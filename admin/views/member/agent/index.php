<?php

/**
 * 后台代理推广关系卡片树页面。
 *
 * @var array{memberCount: int, rootMemberCount: int, agentMemberCount: int} $overview 推广关系统计概览
 */

use app\assets\AppAsset;
use app\helpers\Json;
use app\helpers\Url;
use yii\helpers\Html;

$this->registerCssFile(Url::to('@web/css/member.agent.css'), [
    'depends' => AppAsset::class,
]);

$treeConfig = Html::encode(Json::encode([
    'childrenUrl' => Url::to(['children']),
]));

?>
<div class="member-agent-page">
    <div class="row g-3 mb-3">
        <div class="col-12 col-md-4">
            <div class="card member-agent-stat-card h-100 mb-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="member-agent-stat-icon text-bg-primary">
                        <i class="fa fa-users" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="text-secondary small">会员总数</div>
                        <div class="member-agent-stat-value"><?= (int) $overview['memberCount'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card member-agent-stat-card h-100 mb-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="member-agent-stat-icon text-bg-success">
                        <i class="fa fa-sitemap" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="text-secondary small">代理会员数</div>
                        <div class="member-agent-stat-value"><?= (int) $overview['agentMemberCount'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card member-agent-stat-card h-100 mb-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <span class="member-agent-stat-icon text-bg-warning">
                        <i class="fa fa-user" aria-hidden="true"></i>
                    </span>
                    <div>
                        <div class="text-secondary small">根会员数</div>
                        <div class="member-agent-stat-value"><?= (int) $overview['rootMemberCount'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card member-agent-tree-card mb-0">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h3 class="card-title h6 mb-0">
                <i class="fa fa-code-fork me-2" aria-hidden="true"></i>推广关系图
            </h3>
            <div class="d-flex align-items-center gap-2 text-secondary small">
                <span>点击会员卡片展开或收起直属下级</span>
                <span class="badge text-bg-light border">按需加载</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="member-agent-tree"
                 class="member-agent-tree-viewport"
                 data-config="<?= $treeConfig ?>">
                <div class="member-agent-tree-canvas">
                    <svg class="member-agent-tree-lines" aria-hidden="true"></svg>
                    <div class="member-agent-tree-forest"></div>
                </div>
                <div class="member-agent-tree-state">
                    <span class="spinner-border spinner-border-sm text-primary" aria-hidden="true"></span>
                    <span>正在加载推广关系...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->registerJs(<<<'JS'
seajs.use(['member.agent'], function (agentTree) {
    agentTree.init('#member-agent-tree');
});
JS);
?>
