<?php

/**
 * 后台在线客服三栏工作台。
 *
 * @var int $memberTotal 会员总人数
 * @var array<string, mixed> $workbenchConfig 工作台前端安全配置
 */

use app\helpers\Html;
use app\helpers\Json;

?>
<div id="kefu-workbench" class="kefu-workbench">
    <aside class="kefu-floating-panel kefu-conversation-panel">
        <header class="kefu-panel-header kefu-drag-handle">
            <h1><i class="fa-regular fa-message" aria-hidden="true"></i> 会话列表</h1>
            <span class="kefu-conversation-summary">
                <strong class="kefu-online-count">0</strong>在线 / <?= (int) $memberTotal ?>
            </span>
        </header>
        <div class="kefu-conversation-search">
            <input type="search" class="form-control kefu-member-search"
                   placeholder="搜索会员ID、昵称或用户名" aria-label="搜索会员" autocomplete="off">
        </div>
        <div class="kefu-conversation-list-wrap">
            <table class="table kefu-conversation-table mb-0">
                <tbody class="kefu-conversation-list"></tbody>
            </table>
        </div>
    </aside>

    <section class="kefu-floating-panel kefu-chat-window">
        <main class="kefu-chat-panel">
        <header class="kefu-chat-header kefu-drag-handle">
            <div class="d-flex align-items-center gap-3 min-w-0">
                <span class="kefu-avatar kefu-chat-avatar">客</span>
                <div class="min-w-0">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="kefu-chat-title mb-0">请选择客户</h2>
                        <span class="badge rounded-pill text-bg-light border kefu-chat-status">未选择</span>
                        <span class="kefu-realtime-status is-connecting">实时连接中</span>
                    </div>
                    <div class="small text-muted kefu-chat-subtitle">从左侧会话列表选择一位客户开始接待</div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary kefu-claim">领取会话</button>
                <button type="button" class="btn btn-sm btn-outline-danger kefu-end">结束</button>
            </div>
        </header>

        <section class="kefu-messages" aria-live="polite">
            <div class="kefu-chat-empty">
                <i class="fa fa-regular fa-comments" aria-hidden="true"></i>
                <p>选择客户后显示聊天消息</p>
            </div>
        </section>

        <footer class="kefu-composer">
            <div class="kefu-composer-toolbar">
                <div class="kefu-emoji-wrap">
                    <button type="button" class="btn btn-sm btn-light border kefu-emoji-toggle"
                            aria-expanded="false" aria-controls="kefu-emoji-panel">
                        <i class="fa fa-regular fa-face-smile" aria-hidden="true"></i> 表情
                    </button>
                    <div id="kefu-emoji-panel" class="kefu-emoji-panel" hidden></div>
                </div>
                <button type="button" class="btn btn-sm btn-light border kefu-media-select" data-accept="image/*">
                    <i class="fa fa-regular fa-image" aria-hidden="true"></i> 图片
                </button>
                <button type="button" class="btn btn-sm btn-light border kefu-audio-record" aria-pressed="false">
                    <i class="fa fa-solid fa-microphone" aria-hidden="true"></i> 语音
                </button>
                <button type="button" class="btn btn-sm btn-light border kefu-media-select" data-accept="video/*">
                    <i class="fa fa-solid fa-video" aria-hidden="true"></i> 视频
                </button>
                <input class="d-none kefu-media-input" type="file"
                       accept="<?= Html::encode($workbenchConfig['acceptedMediaTypes']) ?>">
                <select class="form-select form-select-sm kefu-quick-reply" aria-label="快捷回复">
                    <option value="">快捷回复</option>
                </select>
                <select class="form-select form-select-sm kefu-target-user" aria-label="转交客服">
                    <option value="">选择转交客服</option>
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary kefu-transfer">转交</button>
            </div>
            <small class="kefu-media-status text-muted">未选择媒体</small>
            <div class="kefu-composer-input">
                <textarea class="form-control kefu-content" maxlength="5000" rows="3"
                          placeholder="输入消息，回车发送，Shift+回车换行…" aria-label="消息内容"></textarea>
                <button type="button" class="btn btn-primary kefu-send">
                    <i class="fa fa-regular fa-paper-plane" aria-hidden="true"></i> 发送
                </button>
            </div>
        </footer>
        </main>

        <aside class="kefu-detail-panel">
        <nav class="nav nav-tabs nav-fill kefu-detail-tabs" role="tablist">
            <button type="button" class="btn btn-sm btn-light kefu-detail-switch kefu-detail-prev"
                    aria-label="切换到上一个区块" title="上一个区块">
                <i class="fa fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button class="nav-link" type="button" data-bs-toggle="tab"
                    data-bs-target="#kefu-member-detail" role="tab">客户详情</button>
            <button class="nav-link active" type="button" data-bs-toggle="tab"
                    data-bs-target="#kefu-conversation-history" role="tab">历史记录</button>
            <button type="button" class="btn btn-sm btn-light kefu-detail-switch kefu-detail-next"
                    aria-label="切换到下一个区块" title="下一个区块">
                <i class="fa fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
        </nav>
        <div class="tab-content kefu-detail-content">
            <section id="kefu-member-detail" class="tab-pane fade kefu-member-detail" role="tabpanel">
                <div class="kefu-detail-empty">请选择左侧客户</div>
            </section>
            <section id="kefu-conversation-history" class="tab-pane fade show active kefu-conversation-history" role="tabpanel">
                <div class="kefu-detail-empty">请选择左侧客户</div>
            </section>
        </div>
        </aside>
    </section>
</div>

<script>
(function ($) {
    'use strict';

    // 页面只传递服务端生成的安全配置，具体交互由客服领域模块负责。
    seajs.use(['admin.kefu'], function (kefu) {
        kefu.init('#kefu-workbench', <?= Json::htmlEncode($workbenchConfig) ?>);
    });
})(window.jQuery);
</script>
