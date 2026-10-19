<?php /** 多彩种最新开奖组件，由同页切换模块填入真实数据。 */ ?>
    <section class="portal-current" aria-label="最新开奖">
        <div class="portal-current-grid">
            <div class="portal-current-heading">
                <div class="portal-current-issue">最新:<span data-region="issue">--</span>期数</div>
                <button type="button" class="portal-scratch-trigger" data-action="scratch" aria-haspopup="dialog" aria-controls="portal-scratch-dialog" disabled>咪牌</button>
            </div>
            <div class="portal-current-result">
                <div class="portal-current-numbers" data-region="balls"></div>
                <div class="portal-current-summary" data-region="summary"></div>
            </div>
            <div class="portal-countdown" data-block="countdown" role="timer" aria-live="off" title="按本站最近已采集的开奖间隔估算">
                <span>下一期：</span><span data-region="countdown-digits" hidden><b data-time="minutes">0</b><span>分</span><b data-time="seconds">00</b><span>秒</span></span><span data-region="countdown-state">等待开奖时间</span>
            </div>
        </div>
        <p class="portal-state" data-region="status" role="status" hidden></p>
    </section>
