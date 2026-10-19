<?php /** 七个彩种共用的数据库历史分页控件。 */ ?>
<nav class="portal-pagination" data-history-pagination aria-label="开奖记录分页">
    <button type="button" data-page-action="first">回到最新</button>
    <button type="button" data-page-action="previous" disabled>上一页</button>
    <span data-page-status role="status">正在读取历史记录…</span>
    <button type="button" data-page-action="next" disabled>下一页</button>
    <label>每页 <select data-page-size aria-label="每页期数"><option value="20" selected>20期</option><option value="50">50期</option><option value="100">100期</option></select></label>
    <span class="portal-page-error" data-page-error role="alert" hidden></span>
</nav>
