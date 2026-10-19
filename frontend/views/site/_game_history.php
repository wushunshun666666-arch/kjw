<?php
/** 多彩种历史分析及咪牌组件，数据由同页切换模块填入。 */
use yii\helpers\Html;
use yii\helpers\Url;
?>
    <nav class="portal-tabs" role="tablist" aria-label="开奖信息">
        <button type="button" role="tab" id="tab-result" aria-selected="true" aria-controls="portal-game-panel" data-tab="result">结果</button>
        <button type="button" role="tab" id="tab-trend" aria-selected="false" aria-controls="portal-game-panel" data-tab="trend">走势</button>
        <button type="button" role="tab" id="tab-statistics" aria-selected="false" aria-controls="portal-game-panel" data-tab="statistics">统计</button>
    </nav>
    <div class="portal-analysis" data-region="analysis-control" role="group" aria-label="分析项目" hidden>
        <span class="portal-analysis-label">分析项目</span>

    </div>
    <div class="portal-options">
        <label>每页 <select data-page-size aria-label="显示期数"><option value="20" selected>20期</option><option value="50">50期</option><option value="100">100期</option></select></label>
        <span data-region="samples"></span>
    </div>
    <section id="portal-game-panel" role="tabpanel" aria-labelledby="tab-result" tabindex="0"><div class="portal-table-scroll" data-region="table"></div></section>
    <p class="portal-rules">走势与统计使用当前页的实际开奖记录。</p>
    <dialog id="portal-scratch-dialog" class="portal-scratch-dialog" aria-labelledby="portal-scratch-title">
        <div class="portal-scratch-card">
            <header class="portal-scratch-top"><h2 id="portal-scratch-title">咪牌</h2><p>第 <strong data-scratch="issue"></strong> 期开奖结果</p></header>
            <div class="portal-scratch-surface" data-scratch="surface">
                <div class="portal-scratch-numbers" data-scratch="numbers"></div>
                <canvas data-scratch="canvas" tabindex="0" role="button" aria-label="刮开开奖号码，或按回车显示全部号码" data-cover-url="<?= Html::encode(Url::to('@web/cover.png')) ?>"></canvas>
            </div>
            <footer class="portal-scratch-footer"><span data-scratch="countdown">下一期：等待开奖时间</span><button type="button" data-scratch="reset">刷新</button></footer>
        </div>
        <button type="button" class="portal-scratch-close" data-scratch="close" aria-label="关闭咪牌">×</button>
    </dialog>
