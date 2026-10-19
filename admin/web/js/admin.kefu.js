/**
 * 后台客服三栏工作台交互模块。
 *
 * 左栏会话列表、中栏聊天主区（头部 + 消息渲染 + 消息输入）、
 * 右栏详情区（Tab 导航 + 客户详情 + 历史记录）。
 * 协调器负责加载会话、处理实时事件并连接各板块回调。
 */
define(function (require, exports) {
    'use strict';

    var ajax = require('admin.ajax');
    var ui = require('admin.ui');
    var websocket = require('admin.websocket');
    var dataKey = 'kefuWorkbench';

    /** 返回名称首字符，用作没有头像时的文字头像。 */
    function avatarText(name) {
        return Array.from(name || '客')[0] || '客';
    }

    /** 根据浏览器 MIME 提示选择消息类型，服务端仍读取文件头复核。 */
    function detectMessageType(mime, messageTypes) {
        if (mime.indexOf('image/') === 0) {
            return messageTypes.image;
        }
        if (mime.indexOf('audio/') === 0) {
            return messageTypes.audio;
        }
        return messageTypes.video;
    }

    // =====================================================================
    // 左栏：会话列表面板
    // =====================================================================

    /**
     * 创建左侧会话与会员列表面板。
     *
     * @param {jQuery} $root 工作台根节点
     * @param {Object} options 页面配置
     * @return {Object} 列表面板公开接口
     */
    function ConversationPanel($root, options) {
        var ns = '.kefuConversation';
        var items = Array.isArray(options.conversations) ? options.conversations : [];
        var onlineMemberIds = {};
        var conversationId = 0;
        var onSelect = null;
        var onSearchInput = null;
        var searchTimer = null;

        /** 返回列表简短时间。 */
        function formatListTime(value) {
            if (!value) {
                return '';
            }
            return String(value).slice(5, 16);
        }

        /** 返回匹配当前关键词的浏览器内存会员数组，不产生后台请求。 */
        function getFilteredItems() {
            var keyword = $.trim($root.find('.kefu-member-search').val()).toLowerCase();
            if (!keyword) {
                return items;
            }
            return items.filter(function (item) {
                return [item.memberId, item.nickname, item.username].some(function (value) {
                    return String(value || '').toLowerCase().indexOf(keyword) !== -1;
                });
            });
        }

        /** 创建一个左侧会员列表行，所有文本均通过jQuery文本节点写入。 */
        function createItem(item) {
            var itemConversationId = Number(item.conversationId) || 0;
            var $row = $('<tr>').addClass('kefu-conversation-item')
                .attr({'data-member-id': item.memberId, 'data-conversation-id': itemConversationId});
            var $title = $('<strong>').addClass('text-truncate').text(
                item.nickname || item.username || ('会员' + item.memberId)
            );
            var $titleRow = $('<div>').addClass('d-flex justify-content-between gap-2 align-items-center kefu-conversation-item-title')
                .append($title);
            if (Number(item.unreadCount) > 0) {
                $titleRow.append($('<span>').addClass('badge rounded-pill text-bg-danger kefu-conversation-unread-count')
                    .text(item.unreadCount));
            }
            var online = Boolean(onlineMemberIds[Number(item.memberId)]);
            var $meta = $('<div>').addClass('kefu-conversation-meta')
                .append($('<span>').addClass('kefu-member-online-dot').attr('title', online ? '在线' : '离线'))
                .append($('<span>').addClass('kefu-account-amount').text('币 ' + (item.availableAmount || '0.0000')))
                .append($('<span>').addClass('kefu-conversation-owner').text(item.ownerName || '无人接待'))
                .append($('<time>').addClass('kefu-conversation-time').text(formatListTime(item.lastMessageTime)));
            var $content = $('<div>').addClass('flex-grow-1 min-w-0')
                .append($titleRow)
                .append($('<div>').addClass('small text-secondary text-truncate kefu-conversation-preview')
                    .text(item.preview || '暂无消息'))
                .append($meta);
            return $row.toggleClass('is-active', itemConversationId > 0 && itemConversationId === conversationId)
                .append($('<td>').append(
                    $('<div>').addClass('d-flex gap-2 align-items-start')
                        .append($('<span>').addClass('kefu-avatar kefu-member-avatar').text(
                            avatarText(item.nickname || item.username)
                        ))
                        .append($content)
                ));
        }

        /** 根据页面内存数组重绘左侧会员列表，不触发网络请求。 */
        function render() {
            var $list = $root.find('.kefu-conversation-list').empty();
            $.each(getFilteredItems(), function (index, item) {
                $list.append(createItem(item));
            });
            if (!$list.children().length) {
                $list.append($('<tr>').addClass('kefu-conversation-empty').append(
                    $('<td>').addClass('text-center text-secondary py-4').text('没有匹配的会员')
                ));
            }
            renderOnlineState();
        }

        /** 刷新左侧会员在线状态。 */
        function renderOnlineState() {
            $root.find('.kefu-conversation-item').each(function () {
                var online = Boolean(onlineMemberIds[Number($(this).data('member-id'))]);
                $(this).toggleClass('is-online', online)
                    .find('.kefu-member-online-dot').attr('title', online ? '在线' : '离线');
            });
        }

        /** 更新在线会员快照。 */
        function setOnline(memberIds, onlineCount) {
            onlineMemberIds = {};
            $.each(memberIds || [], function (index, memberId) {
                onlineMemberIds[Number(memberId)] = true;
            });
            $root.find('.kefu-online-count').text(Number(onlineCount) || 0);
            renderOnlineState();
        }

        /** 清除当前已读会话在浏览器内存和已渲染行中的未读标记。 */
        function clearUnread(id) {
            $.each(items, function (index, item) {
                if (Number(item.conversationId) === Number(id)) {
                    item.unreadCount = 0;
                }
            });
            $root.find('.kefu-conversation-item[data-conversation-id="' + id + '"]')
                .find('.kefu-conversation-unread-count').remove();
        }

        /** 局部更新一个已渲染的会员行，避免实时消息导致整个会员列表重画。 */
        function updateItemRow($row, item) {
            $row.attr('data-conversation-id', Number(item.conversationId) || 0)
                .toggleClass('is-active', Number(item.conversationId) === conversationId);
            $row.find('.kefu-conversation-preview').text(item.preview || '暂无消息');
            $row.find('.kefu-conversation-time').text(formatListTime(item.lastMessageTime));
            $row.find('.kefu-conversation-owner').text(item.ownerName || '无人接待');
            $row.find('.kefu-conversation-unread-count').remove();
            if (Number(item.unreadCount) > 0) {
                $row.find('.kefu-conversation-item-title').append(
                    $('<span>').addClass('badge rounded-pill text-bg-danger kefu-conversation-unread-count')
                        .text(item.unreadCount)
                );
            }
        }

        /** 将收到会员新消息的会员移至内存列表首位，并且只移动对应的已渲染行。 */
        function moveToTop(event) {
            if (Number(event.senderType) !== 1000) {
                return;
            }
            var itemIndex = items.findIndex(function (item) {
                return Number(item.memberId) === Number(event.memberId);
            });
            if (itemIndex < 0) {
                return;
            }
            var item = items.splice(itemIndex, 1)[0];
            item.conversationId = Number(event.conversationId) || item.conversationId;
            item.preview = event.messagePreview || '新消息';
            item.lastMessageTime = event.messageTime || item.lastMessageTime;
            item.unreadCount = Number(item.conversationId) === conversationId
                ? 0
                : (Number(item.unreadCount) || 0) + 1;
            items.unshift(item);
            var $list = $root.find('.kefu-conversation-list');
            var $row = $list.find('.kefu-conversation-item[data-member-id="' + item.memberId + '"]');
            if ($row.length) {
                updateItemRow($row, item);
                $row.detach().prependTo($list);
                $root.find('.kefu-conversation-list-wrap').scrollTop(0);
                return;
            }
            if (getFilteredItems().some(function (filteredItem) {
                return Number(filteredItem.memberId) === Number(item.memberId);
            })) {
                $list.find('.kefu-conversation-empty').remove();
                $list.prepend(createItem(item));
                $root.find('.kefu-conversation-list-wrap').scrollTop(0);
            }
        }

        /** 更新指定会员在内存列表和 DOM 中的会话编号和接待人。 */
        function updateItemConversationId(memberId, newId, ownerName) {
            $.each(items, function (index, item) {
                if (Number(item.memberId) === Number(memberId)) {
                    item.conversationId = newId;
                    if (ownerName !== undefined) {
                        item.ownerName = ownerName;
                    }
                }
            });
            $root.find('.kefu-conversation-item[data-member-id="' + memberId + '"]').each(function () {
                var item = items.find(function (candidate) {
                    return Number(candidate.memberId) === Number(memberId);
                });
                if (item) {
                    updateItemRow($(this), item);
                }
            });
        }

        /** 设置当前选中的会话编号，并高亮对应行。 */
        function setActiveConversation(id) {
            conversationId = Number(id) || 0;
            $root.find('.kefu-conversation-item').removeClass('is-active');
            $root.find('.kefu-conversation-item[data-conversation-id="' + conversationId + '"]').addClass('is-active');
        }

        function bindEvents() {
            $root.on('click' + ns, '.kefu-conversation-item', function () {
                var $item = $(this);
                var itemConversationId = Number($item.attr('data-conversation-id')) || 0;
                var memberId = Number($item.attr('data-member-id')) || 0;
                if (onSelect) {
                    onSelect(memberId, itemConversationId);
                }
            });
            $root.on('input' + ns, '.kefu-member-search', function () {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(function () {
                    if (onSearchInput) {
                        onSearchInput();
                    }
                }, 150);
            });
        }

        function init() {
            bindEvents();
            render();
        }

        function destroy() {
            window.clearTimeout(searchTimer);
            $root.off(ns);
        }

        return {
            init: init,
            destroy: destroy,
            render: render,
            setOnline: setOnline,
            clearUnread: clearUnread,
            moveToTop: moveToTop,
            updateItemConversationId: updateItemConversationId,
            setActiveConversation: setActiveConversation,
            setOnSelect: function (fn) { onSelect = typeof fn === 'function' ? fn : null; },
            setOnSearchInput: function (fn) { onSearchInput = typeof fn === 'function' ? fn : null; }
        };
    }

    // =====================================================================
    // 中栏 - 小板块：聊天头部
    // =====================================================================

    /**
     * 创建中栏聊天头部板块，管理标题、状态、头像、实时连接和领取/结束按钮。
     *
     * @param {jQuery} $root 工作台根节点
     * @return {Object} 头部板块公开接口
     */
    function ChatHeaderBar($root) {
        var ns = '.kefuChatHeader';
        var onClaim = null;
        var onEnd = null;

        /** 根据当前客服的会话归属控制领取与结束按钮。 */
        function setSendCapability(canSend) {
            $root.find('.kefu-claim')
                .prop('disabled', canSend)
                .text(canSend ? '接待中' : '领取会话');
        }

        /** 渲染头部会员信息和会话状态。 */
        function render(member, conversation) {
            $root.find('.kefu-chat-title').text(member.nickname || member.username || ('会员' + member.id));
            $root.find('.kefu-chat-status').text(conversation.statusName);
            $root.find('.kefu-chat-subtitle').text(
                '会员ID ' + member.id + ' · 游戏币 ' + (member.availableAmount || '0.0000')
            );
            $root.find('.kefu-chat-avatar').text(avatarText(member.nickname || member.username));
        }

        /** 更新工作台头部实时连接状态。 */
        function setRealtimeStatus(status) {
            var labels = {
                connected: '实时已连接',
                connecting: '实时连接中',
                reconnecting: '实时重连中',
                disconnected: '实时已断开',
                error: '实时连接异常',
                disabled: '实时未配置'
            };
            $root.find('.kefu-realtime-status')
                .removeClass('is-connected is-connecting is-disconnected')
                .addClass(status === 'connected' ? 'is-connected' :
                    (status === 'connecting' || status === 'reconnecting' ? 'is-connecting' : 'is-disconnected'))
                .text(labels[status] || '实时已断开');
        }

        function bindEvents() {
            $root.on('click' + ns, '.kefu-claim', function () {
                if (onClaim) { onClaim(); }
            });
            $root.on('click' + ns, '.kefu-end', function () {
                if (onEnd) { onEnd(); }
            });
        }

        function init() {
            bindEvents();
        }

        function destroy() {
            $root.off(ns);
        }

        return {
            init: init,
            destroy: destroy,
            render: render,
            setSendCapability: setSendCapability,
            setRealtimeStatus: setRealtimeStatus,
            setOnClaim: function (fn) { onClaim = typeof fn === 'function' ? fn : null; },
            setOnEnd: function (fn) { onEnd = typeof fn === 'function' ? fn : null; }
        };
    }

    // =====================================================================
    // 中栏 - 小板块：消息渲染区
    // =====================================================================

    /**
     * 创建中栏消息渲染区板块。
     *
     * @param {jQuery} $root 工作台根节点
     * @param {Object} options 页面配置
     * @return {Object} 消息区公开接口
     */
    function MessageBoard($root, options) {
        var ns = '.kefuMessageBoard';
        var urls = options.urls;
        var messageTypes = options.messageTypes;
        var conversationId = 0;
        var lastReadMessageId = 0;
        var lastMemberReadMessageId = 0;
        var readRequestPending = false;
        var requestedReadMessageId = 0;
        var readRequestSequence = 0;
        var pendingMessages = {};
        var onRetryFailed = null;
        var onAfterMarkRead = null;

        /** 返回当前本地时间，供尚未完成服务端保存的消息立即展示。 */
        function getLocalMessageTime() {
            var now = new Date();
            var pad = function (value) {
                return String(value).padStart(2, '0');
            };
            return now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate())
                + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        }

        /** 在原聊天气泡内按原始尺寸展开媒体，超出可用宽度时保持等比例缩小。 */
        function expandMedia($source) {
            if ($source.hasClass('is-original')) {
                return;
            }
            $source.addClass('is-original').attr('title', '已按原始尺寸显示');
            $source.closest('.kefu-message-bubble').addClass('is-original');
            if (!$source.is('video')) {
                return;
            }
            var applyVideoSize = function () {
                if (this.videoWidth > 0 && this.videoHeight > 0) {
                    $(this).css({width: this.videoWidth, height: this.videoHeight});
                }
            };
            if ($source[0].readyState >= 1) {
                applyVideoSize.call($source[0]);
                return;
            }
            $source.one('loadedmetadata', applyVideoSize);
        }

        /** 将一条聊天消息安全渲染到指定容器。 */
        function renderMessageInto($box, message) {
            if (message.messageType === messageTypes.system) {
                $('<div>').addClass('kefu-system-message').text(message.content || '系统消息').appendTo($box);
                return $();
            }

            var mine = Number(message.senderType) !== 1000;
            var $row = $('<article>').addClass('kefu-message-row').toggleClass('is-mine', mine)
                .toggleClass('is-incoming', !mine).toggleClass('is-pending', Boolean(message.pending))
                .toggleClass('is-failed', Boolean(message.failed)).attr('data-message-id', message.id || '');
            if (message.clientMessageId) {
                $row.attr('data-client-message-id', message.clientMessageId);
            }
            var $contentRow = $('<div>').addClass('kefu-message-content-row');
            var $avatar = $('<span>').addClass('kefu-avatar kefu-message-avatar')
                .text(avatarText(mine ? '客服' : message.senderName));
            var $body = $('<div>').addClass('kefu-message-body');
            var $meta = $('<div>').addClass('kefu-message-meta');
            var $bubble = $('<div>').addClass('kefu-message-bubble');

            $meta.append(
                $('<span>').addClass('kefu-message-sender').text(mine ? '客服' : message.senderName),
                $('<time>').addClass('kefu-message-time').text(message.createTime || ''),
                $('<span>').addClass('kefu-message-read-status').text(
                    message.pending ? '发送中' : (message.failed ? '发送失败' : (message.readTime ? '已读' : '未读'))
                )
            );
            if (message.pending) {
                $meta.append(
                    $('<i>').addClass('fa fa-solid fa-spinner fa-spin kefu-message-pending-indicator')
                        .attr({'aria-hidden': 'true', title: '正在发送'})
                );
            }
            if (message.messageType === messageTypes.image) {
                $bubble.addClass('has-media').append(
                    $('<img>').addClass('rounded kefu-media-preview kefu-media-preview-image')
                        .attr({src: message.mediaUrl, alt: '聊天图片', title: '双击查看原始尺寸'})
                );
            } else if (message.messageType === messageTypes.audio) {
                $bubble.addClass('has-media').append(
                    $('<audio>').addClass('kefu-media-preview kefu-media-preview-audio')
                        .attr({controls: true, src: message.mediaUrl, title: '双击查看原始尺寸'})
                );
            } else if (message.messageType === messageTypes.video) {
                $bubble.addClass('has-media').append(
                    $('<video>').addClass('rounded kefu-media-preview kefu-media-preview-video')
                        .attr({controls: true, src: message.mediaUrl, title: '双击查看原始尺寸'})
                );
            } else {
                $bubble.text(message.content || '[系统消息]');
            }
            $body.append($meta, $bubble);
            $contentRow.append($avatar, $body);
            $row.append($contentRow).appendTo($box);
            return $row;
        }

        /** 将一条聊天消息追加到消息区；已经存在的真实消息ID不再重复渲染。 */
        function appendMessage(message) {
            var messageId = Number(message.id) || 0;
            if (messageId && $root.find('.kefu-message-row[data-message-id="' + messageId + '"]').length) {
                return $();
            }
            if (messageId && message.clientMessageId && pendingMessages[message.clientMessageId]) {
                updatePendingMessage(message.clientMessageId, messageId, false);
                return $();
            }
            return renderMessageInto($root.find('.kefu-messages'), message);
        }

        /** 渲染乐观发送的消息并登记到 pending 表，返回新生成的行。 */
        function appendOptimisticMessage(message, clientMessageId, payload) {
            if (!message.createTime) {
                message.createTime = getLocalMessageTime();
            }
            var $row = renderMessageInto($root.find('.kefu-messages'), message);
            pendingMessages[clientMessageId] = {
                conversationId: conversationId,
                payload: payload,
                row: $row,
                sending: true
            };
            return $row;
        }

        /** 更新乐观消息的发送状态，并保留真实消息ID供阅读状态逻辑使用。 */
        function updatePendingMessage(clientMessageId, messageId, failed) {
            var pending = pendingMessages[clientMessageId];
            if (!pending) {
                return;
            }
            pending.sending = false;
            var $row = pending.row;
            $row.removeClass('is-pending').toggleClass('is-failed', Boolean(failed));
            if (messageId) {
                $row.attr('data-message-id', messageId);
            }
            $row.find('.kefu-message-pending-indicator').remove();
            $row.find('.kefu-message-read-status').text(
                failed ? '发送失败，点击重试' : (Number(messageId) <= lastMemberReadMessageId ? '已读' : '未读')
            );
            if (!failed) {
                delete pendingMessages[clientMessageId];
            }
        }

        /** 将失败的乐观消息重新提交，沿用客户端消息编号保证服务端幂等。 */
        function retryPendingMessage(clientMessageId) {
            var pending = pendingMessages[clientMessageId];
            if (!pending || pending.sending || Number(pending.conversationId) !== conversationId) {
                return;
            }
            pending.sending = true;
            pending.row.removeClass('is-failed').addClass('is-pending');
            pending.row.find('.kefu-message-read-status').text('发送中');
            pending.row.find('.kefu-message-meta').append(
                $('<i>').addClass('fa fa-solid fa-spinner fa-spin kefu-message-pending-indicator')
                    .attr({'aria-hidden': 'true', title: '正在发送'})
            );
            if (onRetryFailed) {
                onRetryFailed(clientMessageId, pending.payload, pending.conversationId);
            }
        }

        /** 根据会员已读回执更新当前会话中客服已发送消息的读取状态。 */
        function setReadReceipt(lastReadId) {
            var numericLastReadId = Number(lastReadId) || 0;
            if (numericLastReadId < 1) {
                return;
            }
            lastMemberReadMessageId = Math.max(lastMemberReadMessageId, numericLastReadId);
            $root.find('.kefu-message-row.is-mine').each(function () {
                if (Number($(this).attr('data-message-id')) <= numericLastReadId) {
                    $(this).find('.kefu-message-read-status').text('已读');
                }
            });
        }

        /**
         * 客服读取会员消息后，立即刷新当前会话内会员消息的已读文案。
         *
         * 读取状态以服务端成功响应为准，避免请求失败时在界面中出现错误的“已读”。
         */
        function setUserReadReceipt(lastReadId) {
            var numericLastReadId = Number(lastReadId) || 0;
            if (numericLastReadId < 1) {
                return;
            }
            $root.find('.kefu-message-row.is-incoming').each(function () {
                var $message = $(this);
                var messageId = Number($message.attr('data-message-id')) || 0;
                if (messageId > 0 && messageId <= numericLastReadId) {
                    $message.find('.kefu-message-read-status').text('已读');
                }
            });
        }

        /** 清空聊天消息区。 */
        function clearMessages() {
            $root.find('.kefu-messages').empty();
        }

        /** 显示历史定位提示并清空当前消息区。 */
        function setHistoryLocation(date) {
            $root.find('.kefu-messages')
                .empty()
                .append($('<div>').addClass('kefu-history-location').text('正在查看 ' + date + ' 的历史消息'));
        }

        /** 滚动到顶部。 */
        function scrollTop() {
            $root.find('.kefu-messages').scrollTop(0);
        }

        /** 滚动到底部。 */
        function scrollBottom() {
            var $box = $root.find('.kefu-messages');
            $box.scrollTop($box.prop('scrollHeight'));
        }

        /** 仅把当前前台窗口中实际进入可视区域的会员消息提交为已读。 */
        function markVisibleRead() {
            if (!conversationId || document.visibilityState !== 'visible' || !document.hasFocus()) {
                return;
            }
            var $box = $root.find('.kefu-messages');
            if (!$box.length) {
                return;
            }
            var boxRect = $box[0].getBoundingClientRect();
            var visibleMessageId = 0;
            $root.find('.kefu-message-row.is-incoming').each(function () {
                var rowRect = this.getBoundingClientRect();
                if (rowRect.bottom > boxRect.top && rowRect.top < boxRect.bottom) {
                    visibleMessageId = Math.max(visibleMessageId, Number($(this).data('message-id')));
                }
            });
            if (visibleMessageId <= lastReadMessageId) {
                return;
            }
            requestedReadMessageId = Math.max(requestedReadMessageId, visibleMessageId);
            submitVisibleRead();
        }

        /**
         * 串行上报客服当前可见的会员消息，并仅在服务端确认后推进本地已读游标。
         *
         * 网络瞬断或接口失败时保留游标，下次滚动、切换焦点或追加消息会自动重试。
         */
        function submitVisibleRead() {
            if (readRequestPending || !conversationId || requestedReadMessageId <= lastReadMessageId) {
                return;
            }
            var requestedConversationId = conversationId;
            var submittedMessageId = requestedReadMessageId;
            var requestSequence = ++readRequestSequence;
            readRequestPending = true;
            ajax.post(
                urls.markRead + '?id=' + requestedConversationId,
                {last_message_id: submittedMessageId},
                function (result) {
                    if (requestSequence !== readRequestSequence) {
                        return;
                    }
                    readRequestPending = false;
                    if (requestedConversationId !== conversationId || !result.success()) {
                        return;
                    }
                    lastReadMessageId = Math.max(lastReadMessageId, submittedMessageId);
                    setUserReadReceipt(lastReadMessageId);
                    if (onAfterMarkRead) {
                        onAfterMarkRead(conversationId);
                    }
                    submitVisibleRead();
                },
                function () {
                    if (requestSequence !== readRequestSequence) {
                        return;
                    }
                    readRequestPending = false;
                }
            );
        }

        function setConversation(id) {
            conversationId = Number(id) || 0;
            lastReadMessageId = 0;
            lastMemberReadMessageId = 0;
            requestedReadMessageId = 0;
            readRequestPending = false;
            readRequestSequence += 1;
        }

        function isCurrentConversation(id) {
            return Number(id) === conversationId;
        }

        function bindEvents() {
            $root.on('click' + ns, '.kefu-message-row.is-failed', function () {
                var clientMessageId = $(this).attr('data-client-message-id');
                if (clientMessageId) {
                    retryPendingMessage(clientMessageId);
                }
            });
            $root.on('dblclick' + ns, '.kefu-media-preview', function (event) {
                event.preventDefault();
                event.stopPropagation();
                expandMedia($(this));
            });
            $root.on('scroll' + ns, '.kefu-messages', markVisibleRead);
        }

        function init() {
            bindEvents();
        }

        function destroy() {
            $root.off(ns);
            pendingMessages = {};
        }

        return {
            init: init,
            destroy: destroy,
            appendMessage: appendMessage,
            appendOptimisticMessage: appendOptimisticMessage,
            updatePendingMessage: updatePendingMessage,
            setReadReceipt: setReadReceipt,
            clearMessages: clearMessages,
            setHistoryLocation: setHistoryLocation,
            scrollTop: scrollTop,
            scrollBottom: scrollBottom,
            markVisibleRead: markVisibleRead,
            setConversation: setConversation,
            isCurrentConversation: isCurrentConversation,
            setOnRetryFailed: function (fn) { onRetryFailed = typeof fn === 'function' ? fn : null; },
            setOnAfterMarkRead: function (fn) { onAfterMarkRead = typeof fn === 'function' ? fn : null; }
        };
    }

    // =====================================================================
    // 中栏 - 小板块：消息输入区（含 emoji / 录音 / 媒体 / 输入 / 发送）
    // =====================================================================

    /**
     * 创建中栏消息输入区板块，管理工具栏、emoji、录音、媒体上传、输入和发送。
     *
     * @param {jQuery} $root 工作台根节点
     * @param {Object} options 页面配置
     * @return {Object} 输入区公开接口
     */
    function MessageComposer($root, options) {
        var ns = '.kefuComposer';
        var urls = options.urls;
        var messageTypes = options.messageTypes;
        var canSendMessage = false;
        var pendingMedia = null;

        // 录音状态
        var mediaRecorder = null;
        var recordingStream = null;
        var audioChunks = [];

        // 回调
        var onSend = null;
        var onTransfer = null;

        // -----------------------------------------------------------------
        // 发送能力与输入
        // -----------------------------------------------------------------

        /** 根据当前客服的会话归属控制消息输入与媒体操作。 */
        function setSendCapability(canSend) {
            canSendMessage = Boolean(canSend);
            $root.find('.kefu-content, .kefu-send, .kefu-emoji-toggle, .kefu-media-select, .kefu-audio-record, .kefu-quick-reply')
                .prop('disabled', !canSendMessage);
            if (!canSendMessage) {
                cancelAudioRecording();
                closeEmojiPanel();
            }
        }

        function canSend() {
            return canSendMessage;
        }

        /** 返回输入框内容。 */
        function getInputContent() {
            return String($root.find('.kefu-content').val() || '');
        }

        /** 追加文本到输入框并触发聚焦。 */
        function appendInput(text) {
            var $content = $root.find('.kefu-content');
            $content.val($content.val() + text).trigger('focus');
        }

        /** 设置输入框内容并触发聚焦。 */
        function setInputContent(content) {
            $root.find('.kefu-content').val(content).trigger('focus');
        }

        // -----------------------------------------------------------------
        // 待发送媒体
        // -----------------------------------------------------------------

        /** 返回当前待发送媒体的类型。 */
        function getPendingMediaType() {
            return pendingMedia ? pendingMedia.messageType : messageTypes.text;
        }

        /** 返回当前待发送媒体的 key。 */
        function getPendingMediaKey() {
            return pendingMedia ? pendingMedia.key : '';
        }

        /** 返回当前待发送媒体的对象 URL。 */
        function getPendingMediaUrl() {
            return pendingMedia && pendingMedia.file ? URL.createObjectURL(pendingMedia.file) : null;
        }

        /** 设置媒体状态提示。 */
        function setMediaStatus(text) {
            $root.find('.kefu-media-status').text(text);
        }

        /** 清空待发送媒体。 */
        function clearPendingMedia() {
            pendingMedia = null;
        }

        /** 清空输入框和媒体输入。 */
        function clearAfterSend() {
            pendingMedia = null;
            $root.find('.kefu-content, .kefu-media-input').val('');
            setMediaStatus('未选择媒体');
        }

        /** 上传已选择或已录制的媒体，并在通过服务端校验后保留为待发送消息。 */
        function uploadMedia(file, messageType) {
            var formData = new FormData();
            formData.append('media', file);
            formData.append('message_type', messageType);
            formData.append(yii.getCsrfParam(), yii.getCsrfToken());
            setMediaStatus('正在安全检测并上传…');
            ajax.post(urls.upload, formData, function (result) {
                if (!result.success()) {
                    pendingMedia = null;
                    setMediaStatus('上传失败');
                    ui.errorBox(result.message());
                    return;
                }
                pendingMedia = {key: result.data().key, messageType: messageType, file: file};
                setMediaStatus(file.name + '（已通过检测，点击发送）');
            }, null, null, {processData: false, contentType: false});
        }

        // -----------------------------------------------------------------
        // emoji 面板
        // -----------------------------------------------------------------

        /** 切换 emoji 面板显示。 */
        function toggleEmojiPanel(event) {
            if (event) {
                event.stopPropagation();
            }
            var $panel = $root.find('.kefu-emoji-panel');
            $panel.prop('hidden', !$panel.prop('hidden'));
            $root.find('.kefu-emoji-toggle').attr('aria-expanded', !$panel.prop('hidden') ? 'true' : 'false');
        }

        /** 关闭 emoji 面板。 */
        function closeEmojiPanel() {
            $root.find('.kefu-emoji-panel').prop('hidden', true);
            $root.find('.kefu-emoji-toggle').attr('aria-expanded', 'false');
        }

        /** 填充 emoji 面板按钮。 */
        function fillEmojis() {
            var $panel = $root.find('.kefu-emoji-panel');
            $.each(options.emojis || [], function (index, emoji) {
                $('<button>').attr({type: 'button', title: emoji}).addClass('kefu-emoji-option')
                    .text(emoji).appendTo($panel);
            });
        }

        // -----------------------------------------------------------------
        // 语音录制
        // -----------------------------------------------------------------

        /** 释放录音流并恢复语音按钮初始状态。 */
        function resetAudioRecorder() {
            if (recordingStream) {
                $.each(recordingStream.getTracks(), function (index, track) {
                    track.stop();
                });
            }
            recordingStream = null;
            mediaRecorder = null;
            audioChunks = [];
            $root.find('.kefu-audio-record').removeClass('btn-danger').addClass('btn-light')
                .attr('aria-pressed', 'false').html('<i class="fa fa-solid fa-microphone" aria-hidden="true"></i> 语音');
        }

        /** 停止当前录音；停止事件会将生成的语音上传为待发送媒体。 */
        function stopAudioRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
            }
        }

        /** 放弃未完成录音，避免会话失去接待权限后仍向原会话上传语音。 */
        function cancelAudioRecording() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.onstop = null;
                mediaRecorder.stop();
            }
            resetAudioRecorder();
        }

        /** 开始录音或结束正在进行的录音。 */
        function toggleAudioRecording() {
            if (!canSendMessage) {
                ui.errorBox('请先领取会话后再录制语音。');
                return;
            }
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                setMediaStatus('正在结束录音…');
                stopAudioRecording();
                return;
            }
            if (!window.isSecureContext) {
                ui.errorBox('语音录制仅支持 HTTPS 或 localhost。当前页面为不安全 HTTP，请先配置 HTTPS。');
                return;
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) {
                ui.errorBox('当前浏览器不支持语音录制，请使用支持麦克风录制的浏览器。');
                return;
            }
            navigator.mediaDevices.getUserMedia({audio: true}).then(function (stream) {
                recordingStream = stream;
                var recorderOptions = {};
                if (MediaRecorder.isTypeSupported('audio/webm;codecs=opus')) {
                    recorderOptions.mimeType = 'audio/webm;codecs=opus';
                }
                mediaRecorder = new MediaRecorder(stream, recorderOptions);
                audioChunks = [];
                mediaRecorder.ondataavailable = function (event) {
                    if (event.data.size > 0) {
                        audioChunks.push(event.data);
                    }
                };
                mediaRecorder.onstop = function () {
                    var mimeType = mediaRecorder && mediaRecorder.mimeType ? mediaRecorder.mimeType : 'audio/webm';
                    var audioBlob = new Blob(audioChunks, {type: mimeType});
                    resetAudioRecorder();
                    if (audioBlob.size < 1) {
                        setMediaStatus('录音为空，请重新录制');
                        return;
                    }
                    var extension = mimeType.indexOf('mp4') > -1 ? 'm4a' : 'webm';
                    var audioFile = new File([audioBlob], 'voice-' + Date.now() + '.' + extension, {type: mimeType});
                    uploadMedia(audioFile, messageTypes.audio);
                };
                mediaRecorder.onerror = function () {
                    resetAudioRecorder();
                    ui.errorBox('语音录制异常，请重新录制。');
                };
                mediaRecorder.start();
                $root.find('.kefu-audio-record').removeClass('btn-light').addClass('btn-danger')
                    .attr('aria-pressed', 'true').html('<i class="fa-solid fa fa-stop" aria-hidden="true"></i> 结束录音');
                setMediaStatus('录音中，再次点击"结束录音"后上传');
            }).catch(function () {
                resetAudioRecorder();
                ui.errorBox('无法使用麦克风，请确认已授予浏览器录音权限。');
            });
        }

        // -----------------------------------------------------------------
        // 初始化与销毁
        // -----------------------------------------------------------------

        function fillOptions() {
            $.each(options.quickReplies, function (index, item) {
                $root.find('.kefu-quick-reply').append($('<option>').val(item.id).text(item.title));
            });
            $.each(options.users, function (index, user) {
                $root.find('.kefu-target-user').append($('<option>').val(user.id).text(user.name));
            });
        }

        function bindEvents() {
            $root.on('keydown' + ns, '.kefu-content', function (event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    if (onSend) { onSend(); }
                }
            });
            $root.on('click' + ns, '.kefu-send', function () {
                if (onSend) { onSend(); }
            });
            $root.on('click' + ns, '.kefu-transfer', function () {
                if (onTransfer) { onTransfer($root.find('.kefu-target-user').val()); }
            });
            $root.on('change' + ns, '.kefu-quick-reply', function () {
                var selectedId = parseInt($(this).val(), 10);
                var selected = options.quickReplies.find(function (item) {
                    return item.id === selectedId;
                });
                if (selected) {
                    setInputContent(selected.content);
                }
            });
            $root.on('click' + ns, '.kefu-emoji', function () {
                appendInput($(this).val());
            });
            $root.on('click' + ns, '.kefu-emoji-toggle', toggleEmojiPanel);
            $root.on('click' + ns, '.kefu-emoji-option', function () {
                appendInput($(this).text());
                closeEmojiPanel();
            });
            $(document).on('click' + ns, function (event) {
                if (!$(event.target).closest('.kefu-emoji-wrap').length) {
                    closeEmojiPanel();
                }
            });
            $root.on('click' + ns, '.kefu-media-select', function () {
                var $input = $root.find('.kefu-media-input');
                $input.attr('accept', $(this).data('accept')).trigger('click');
            });
            $root.on('change' + ns, '.kefu-media-input', function () {
                var file = this.files && this.files[0];
                if (!file) {
                    return;
                }
                uploadMedia(file, detectMessageType(file.type, messageTypes));
            });
            $root.on('click' + ns, '.kefu-audio-record', toggleAudioRecording);
        }

        function init() {
            fillOptions();
            fillEmojis();
            bindEvents();
        }

        function destroy() {
            cancelAudioRecording();
            $root.off(ns);
            $(document).off(ns);
            pendingMedia = null;
        }

        return {
            init: init,
            destroy: destroy,
            setSendCapability: setSendCapability,
            canSend: canSend,
            getInputContent: getInputContent,
            appendInput: appendInput,
            setInputContent: setInputContent,
            getPendingMediaType: getPendingMediaType,
            getPendingMediaKey: getPendingMediaKey,
            getPendingMediaUrl: getPendingMediaUrl,
            setMediaStatus: setMediaStatus,
            clearPendingMedia: clearPendingMedia,
            clearAfterSend: clearAfterSend,
            uploadMedia: uploadMedia,
            cancelAudioRecording: cancelAudioRecording,
            closeEmojiPanel: closeEmojiPanel,
            setOnSend: function (fn) { onSend = typeof fn === 'function' ? fn : null; },
            setOnTransfer: function (fn) { onTransfer = typeof fn === 'function' ? fn : null; }
        };
    }

    // =====================================================================
    // 右栏 - 小板块：详情区 Tab 导航
    // =====================================================================

    /**
     * 创建右栏详情区 Tab 导航板块，管理 prev/next 和触摸滑动切换。
     *
     * @param {jQuery} $root 工作台根节点
     * @return {Object} Tab 导航公开接口
     */
    function DetailTabNavigator($root) {
        var ns = '.kefuDetailTab';
        var detailTouchStartX = null;

        /** 按顺序切换右侧客户详情与历史记录区块。 */
        function switchSection(step) {
            var $tabs = $root.find('.kefu-detail-tabs .nav-link');
            var activeIndex = $tabs.index($tabs.filter('.active'));
            var nextIndex = Math.max(0, Math.min($tabs.length - 1, activeIndex + step));
            if (nextIndex === activeIndex || nextIndex < 0) {
                return;
            }
            bootstrap.Tab.getOrCreateInstance($tabs[nextIndex]).show();
        }

        function bindEvents() {
            $root.on('click' + ns, '.kefu-detail-prev', function () {
                switchSection(-1);
            });
            $root.on('click' + ns, '.kefu-detail-next', function () {
                switchSection(1);
            });
            $root.on('touchstart' + ns, '.kefu-detail-content', function (event) {
                detailTouchStartX = event.originalEvent.touches[0].clientX;
            });
            $root.on('touchend' + ns, '.kefu-detail-content', function (event) {
                if (detailTouchStartX === null) {
                    return;
                }
                var deltaX = event.originalEvent.changedTouches[0].clientX - detailTouchStartX;
                detailTouchStartX = null;
                if (Math.abs(deltaX) >= 50) {
                    switchSection(deltaX < 0 ? 1 : -1);
                }
            });
        }

        function init() {
            bindEvents();
        }

        function destroy() {
            $root.off(ns);
            detailTouchStartX = null;
        }

        return {
            init: init,
            destroy: destroy
        };
    }

    // =====================================================================
    // 右栏 - 小板块：客户详情内容
    // =====================================================================

    /**
     * 创建右栏客户详情内容板块。
     *
     * @param {jQuery} $root 工作台根节点
     * @return {Object} 详情内容公开接口
     */
    function MemberDetailContent($root) {
        var ns = '.kefuMemberDetail';

        /** 渲染右侧客户资料。 */
        function render(member) {
            var avatar = member.avatar
                ? $('<img>').addClass('kefu-detail-avatar').attr({src: member.avatar, alt: '会员头像'})
                : $('<span>').addClass('kefu-avatar kefu-detail-avatar').text(avatarText(member.nickname));
            var $header = $('<div>').addClass('kefu-detail-profile').append(avatar);
            var $identity = $('<div>').addClass('min-w-0')
                .append($('<h3>').text(member.nickname || member.username || ('会员' + member.id)))
                .append($('<div>').addClass('text-muted small').text(
                    'ID ' + member.id + ' · ' + (member.username || '未设置用户名')
                ))
                .append($('<span>').addClass('badge text-bg-light border mt-1').text(member.statusName));
            $header.append($identity);

            var $balance = $('<div>').addClass('kefu-detail-balance-grid')
                .append($('<div>').append('<small>可用游戏币</small>')
                    .append($('<strong>').text(member.availableAmount || '0.0000')))
                .append($('<div>').append('<small>冻结游戏币</small>')
                    .append($('<strong>').text(member.frozenAmount || '0.0000')));
            var $fields = $('<dl>').addClass('kefu-detail-fields')
                .append('<dt>手机</dt>', $('<dd>').text(member.mobile || '未填写'))
                .append('<dt>邮箱</dt>', $('<dd>').text(member.email || '未填写'))
                .append('<dt>推广码</dt>', $('<dd>').text(member.inviteCode || '--'))
                .append('<dt>直属上级</dt>', $('<dd>').text(member.parentName || '无'))
                .append('<dt>注册方式</dt>', $('<dd>').text(member.registerTypeName || '--'));
            $root.find('.kefu-member-detail').empty().append($header, $balance, $fields);
        }

        function init() {}

        function destroy() {
            $root.off(ns);
        }

        return {
            init: init,
            destroy: destroy,
            render: render
        };
    }

    // =====================================================================
    // 右栏 - 小板块：历史记录卡片列表
    // =====================================================================

    /**
     * 创建右栏历史记录卡片列表板块。
     *
     * @param {jQuery} $root 工作台根节点
     * @return {Object} 历史卡片列表公开接口
     */
    function HistoryCardList($root) {
        var ns = '.kefuHistoryCardList';
        var onSelectDate = null;

        /** 渲染历史记录卡片列表。 */
        function renderCards(history) {
            var $history = $root.find('.kefu-conversation-history').empty();
            if (!history || !history.length) {
                $history.append($('<div>').addClass('kefu-detail-empty').text('暂无历史聊天记录'));
                return;
            }
            $.each(history, function (index, item) {
                $('<button>').attr({type: 'button', 'data-history-date': item.date})
                    .addClass('kefu-history-card')
                    .append($('<span>').addClass('kefu-history-icon').html('<i class="fa-solid fa fa-history"></i>'))
                    .append($('<div>').addClass('kefu-history-body')
                        .append($('<time>').text(item.date + ' · ' + item.count + '条'))
                        .append($('<strong>').text(item.preview || '聊天记录')))
                    .append($('<i>').addClass('fa fa-solid fa-chevron-right kefu-history-arrow'))
                    .appendTo($history);
            });
        }

        /** 设置当前选中的历史日期。 */
        function setActiveDate(date) {
            $root.find('.kefu-history-card').removeClass('is-active');
            $root.find('.kefu-history-card[data-history-date="' + date + '"]').addClass('is-active');
        }

        function bindEvents() {
            $root.on('click' + ns, '.kefu-history-card', function () {
                var date = $(this).attr('data-history-date');
                if (onSelectDate) {
                    onSelectDate(date);
                }
            });
        }

        function init() {
            bindEvents();
        }

        function destroy() {
            $root.off(ns);
        }

        return {
            init: init,
            destroy: destroy,
            renderCards: renderCards,
            setActiveDate: setActiveDate,
            setOnSelectDate: function (fn) { onSelectDate = typeof fn === 'function' ? fn : null; }
        };
    }

    // =====================================================================
    // 浮动面板拖动
    // =====================================================================

    /**
     * 创建浮动面板拖动能力。
     *
     * @param {jQuery} $root 工作台根节点
     * @return {Object} 拖动能力公开接口
     */
    function FloatingPanelDrag($root) {
        var focusNs = '.kefuFloatingFocus';
        var dragNs = '.kefuFloatingDrag';
        var zIndex = 2;

        /** 将被操作的浮动面板提升到最上层，避免两个工作面板相互遮挡。 */
        function bringToFront($panel) {
            zIndex++;
            $panel.css('z-index', zIndex);
        }

        function bindEvents() {
            $root.find('.kefu-floating-panel').each(function () {
                var $panel = $(this);
                $panel.off(focusNs).on('mousedown' + focusNs, function () {
                    bringToFront($panel);
                });
                $panel.find('.kefu-drag-handle')
                    .off(dragNs)
                    .on('mousedown' + dragNs, function (event) {
                        if (event.which !== 1 || $(event.target).closest('button, input, select, textarea, a').length) {
                            return;
                        }
                        if ($panel.css('position') !== 'absolute') {
                            return;
                        }

                        event.preventDefault();
                        bringToFront($panel);
                        var startX = event.pageX;
                        var startY = event.pageY;
                        var startLeft = $panel.position().left;
                        var startTop = $panel.position().top;
                        var maxLeft = Math.max(0, $root.innerWidth() - $panel.outerWidth());
                        var maxTop = Math.max(0, $root.innerHeight() - $panel.outerHeight());
                        $panel.addClass('is-dragging');

                        $(document)
                            .off('mousemove' + dragNs + ' mouseup' + dragNs)
                            .on('mousemove' + dragNs, function (moveEvent) {
                                $panel.css({
                                    left: Math.min(maxLeft, Math.max(0, startLeft + moveEvent.pageX - startX)),
                                    top: Math.min(maxTop, Math.max(0, startTop + moveEvent.pageY - startY))
                                });
                            })
                            .on('mouseup' + dragNs, function () {
                                $panel.removeClass('is-dragging');
                                $(document).off('mousemove' + dragNs + ' mouseup' + dragNs);
                            });
                    });
            });
        }

        function init() {
            bindEvents();
        }

        function destroy() {
            $root.find('.kefu-floating-panel').off(focusNs + ' ' + dragNs);
            $(document).off(dragNs);
        }

        return {
            init: init,
            destroy: destroy
        };
    }

    // =====================================================================
    // 页面协调器
    // =====================================================================

    /**
     * 创建客服工作台页面协调器并连接各业务板块。
     *
     * @param {jQuery} $root 工作台根节点
     * @param {Object} options 服务端生成的页面配置
     * @return {Object} 页面公开接口
     */
    function KefuWorkbenchPage($root, options) {
        var ns = '.kefuWorkbench';
        var urls = options.urls;
        var messageTypes = options.messageTypes;
        var conversationId = 0;
        var historyDate = null;
        var realtimeConnection = null;
        var lastMessageId = 0;
        var loadingConversation = false;
        var loadingIncrementalMessages = false;
        var maxCachedConversations = 12;
        var conversationCache = {};
        var conversationCacheOrder = [];

        // 左栏
        var conversationPanel = new ConversationPanel($root, options);

        // 中栏小板块
        var chatHeader = new ChatHeaderBar($root);
        var messageBoard = new MessageBoard($root, options);
        var messageComposer = new MessageComposer($root, options);

        // 右栏小板块
        var detailTabs = new DetailTabNavigator($root);
        var memberDetail = new MemberDetailContent($root);
        var historyList = new HistoryCardList($root);

        // 浮动面板拖动
        var floatingDrag = new FloatingPanelDrag($root);

        /** 读取会话缓存并将其标记为最近使用，缓存容量由最近使用顺序限制。 */
        function getCachedConversation(id) {
            if (!Object.prototype.hasOwnProperty.call(conversationCache, id)) {
                return null;
            }
            conversationCacheOrder = conversationCacheOrder.filter(function (cachedId) {
                return Number(cachedId) !== Number(id);
            });
            conversationCacheOrder.push(Number(id));
            return conversationCache[id];
        }

        /** 保存完整会话响应；超出容量时淘汰最久未使用的会话数据。 */
        function cacheConversation(id, data) {
            var conversationKey = Number(id);
            conversationCache[conversationKey] = data;
            conversationCacheOrder = conversationCacheOrder.filter(function (cachedId) {
                return Number(cachedId) !== conversationKey;
            });
            conversationCacheOrder.push(conversationKey);
            while (conversationCacheOrder.length > maxCachedConversations) {
                delete conversationCache[conversationCacheOrder.shift()];
            }
        }

        /** 删除会话缓存，供客服接待归属改变后强制使用服务端最新数据。 */
        function clearCachedConversation(id) {
            var conversationKey = Number(id);
            delete conversationCache[conversationKey];
            conversationCacheOrder = conversationCacheOrder.filter(function (cachedId) {
                return Number(cachedId) !== conversationKey;
            });
        }

        /** 将已取得的会话数据渲染到当前工作台，并同步当前消息游标。 */
        function renderConversationData(data) {
            var member = data.conversation.member;
            var canSend = Boolean(data.conversation && data.conversation.canSend);

            conversationPanel.setActiveConversation(conversationId);
            chatHeader.render(member, data.conversation);
            chatHeader.setSendCapability(canSend);
            messageComposer.setSendCapability(canSend);
            memberDetail.render(member);
            historyList.renderCards(data.conversation.history || []);

            messageBoard.clearMessages();
            $.each(data.messages || [], function (index, message) {
                messageBoard.appendMessage(message);
                lastMessageId = Math.max(lastMessageId, Number(message.id) || 0);
            });
            messageBoard.scrollBottom();
            window.requestAnimationFrame(messageBoard.markVisibleRead);
        }

        /** 加载会话；已访问过的会话优先从页面内存缓存即时恢复。 */
        function loadConversation(id, forceRefresh) {
            var requestedConversationId = parseInt(id, 10);
            conversationId = requestedConversationId;
            lastMessageId = 0;
            messageBoard.setConversation(conversationId);
            historyDate = null;
            var cachedConversation = getCachedConversation(conversationId);
            if (cachedConversation && !forceRefresh) {
                loadingConversation = false;
                renderConversationData(cachedConversation);
                loadIncrementalMessages();
                return;
            }

            loadingConversation = true;
            ajax.get(urls.conversation + '?id=' + conversationId, function (result) {
                if (requestedConversationId !== conversationId) {
                    return;
                }
                loadingConversation = false;
                if (!result.success()) {
                    ui.errorBox(result.message());
                    return;
                }

                var data = result.data();
                cacheConversation(conversationId, data);
                renderConversationData(data);
                loadIncrementalMessages();
            }, function () {
                if (requestedConversationId === conversationId) {
                    loadingConversation = false;
                }
            });
        }

        /** 仅请求当前会话中尚未渲染的消息，避免实时事件反复重载整个会话。 */
        function loadIncrementalMessages() {
            if (!conversationId || historyDate !== null || loadingConversation || loadingIncrementalMessages) {
                return;
            }
            var requestedConversationId = conversationId;
            var requestedAfterId = lastMessageId;
            loadingIncrementalMessages = true;
            ajax.get(urls.messages + '?id=' + requestedConversationId + '&after_id=' + requestedAfterId, function (result) {
                loadingIncrementalMessages = false;
                if (requestedConversationId !== conversationId || historyDate !== null) {
                    return;
                }
                if (!result.success()) {
                    return;
                }
                var appended = false;
                $.each(result.data().items || [], function (index, message) {
                    var $row = messageBoard.appendMessage(message);
                    appended = appended || $row.length > 0;
                    lastMessageId = Math.max(lastMessageId, Number(message.id) || 0);
                    var cachedConversation = getCachedConversation(conversationId);
                    if (cachedConversation && Array.isArray(cachedConversation.messages)) {
                        cachedConversation.messages.push(message);
                        cachedConversation.messages = cachedConversation.messages.slice(-50);
                    }
                });
                if (appended) {
                    messageBoard.scrollBottom();
                    window.requestAnimationFrame(messageBoard.markVisibleRead);
                }
                if ((result.data().items || []).length === 50) {
                    loadIncrementalMessages();
                }
            }, function () {
                loadingIncrementalMessages = false;
            });
        }

        /** 为没有历史会话的会员创建会话后再打开聊天区域。 */
        function initiateConversation(memberId) {
            ajax.post(urls.initiate, {member_id: memberId}, function (result) {
                if (!result.success()) {
                    ui.errorBox(result.message());
                    return;
                }
                var createdConversationId = Number(result.data().id) || 0;
                if (!createdConversationId) {
                    ui.errorBox('创建客服会话失败，请刷新页面后重试。');
                    return;
                }
                conversationPanel.updateItemConversationId(memberId, createdConversationId, '我');
                loadConversation(createdConversationId, true);
            });
        }

        /** 执行当前会话写操作。 */
        function run(url, data, successCallback) {
            if (!conversationId) {
                ui.errorBox('请先选择客户。');
                return;
            }
            ajax.post(url + '?id=' + conversationId, data || {}, function (result) {
                if (!result.success()) {
                    var message = result.message();
                    ui.errorBox(typeof message === 'string' && $.trim(message) !== ''
                        ? message
                        : '操作失败，请刷新会话后重试。');
                    return;
                }
                if (typeof successCallback === 'function') {
                    successCallback(result);
                }
                loadConversation(conversationId, true);
            });
        }

        /** 将已即时渲染的消息发送到服务端，并在返回后同步气泡状态。 */
        function submitOptimisticMessage(clientMessageId, payload, sourceConversationId) {
            if (Number(sourceConversationId) !== conversationId) {
                return;
            }
            ajax.post(urls.send + '?id=' + conversationId, payload, function (result) {
                if (!result.success()) {
                    messageBoard.updatePendingMessage(clientMessageId, 0, true);
                    ui.errorBox(result.message() || '消息发送失败，点击气泡可重试。');
                    return;
                }
                var data = result.data() || {};
                var messageId = Number(data.messageId) || 0;
                messageBoard.updatePendingMessage(clientMessageId, messageId, false);
                lastMessageId = Math.max(lastMessageId, messageId);
            }, function (result) {
                messageBoard.updatePendingMessage(clientMessageId, 0, true);
                ui.errorBox(result.message() || '网络异常，点击气泡可重试。');
            });
        }

        /** 发送当前输入框或已上传媒体。 */
        function sendMessage() {
            if (!messageComposer.canSend()) {
                ui.errorBox('请先领取会话后再发送消息。');
                return;
            }
            var messageType = messageComposer.getPendingMediaType();
            var content = messageComposer.getInputContent();
            if (messageType === messageTypes.text && $.trim(content) === '') {
                ui.errorBox('请输入需要发送的消息。');
                return;
            }
            var clientMessageId = 'admin-' + Date.now() + '-' + Math.random().toString(16).slice(2);
            var payload = {
                'SendForm[messageType]': messageType,
                'SendForm[content]': content,
                'SendForm[mediaKey]': messageComposer.getPendingMediaKey(),
                'SendForm[clientMessageId]': clientMessageId
            };
            var mediaUrl = messageComposer.getPendingMediaUrl();
            messageBoard.appendOptimisticMessage({
                id: '',
                senderType: 2000,
                senderName: '客服',
                messageType: messageType,
                content: content,
                mediaUrl: mediaUrl,
                clientMessageId: clientMessageId,
                pending: true
            }, clientMessageId, payload);
            messageBoard.scrollBottom();
            messageComposer.clearAfterSend();
            submitOptimisticMessage(clientMessageId, payload, conversationId);
        }

        /** 加载右栏所选日期的历史消息，并在中间聊天区定位到该日期内容。 */
        function loadHistoryMessages(date) {
            if (!conversationId || !date) {
                return;
            }
            historyDate = date;
            historyList.setActiveDate(date);
            ajax.get(urls.messages + '?id=' + conversationId + '&date=' + encodeURIComponent(date), function (result) {
                if (!result.success()) {
                    ui.errorBox(result.message() || '历史记录加载失败，请重试。');
                    return;
                }
                messageBoard.setHistoryLocation(date);
                $.each(result.data().items || [], function (index, message) {
                    messageBoard.appendMessage(message);
                });
                messageBoard.scrollTop();
                window.requestAnimationFrame(messageBoard.markVisibleRead);
            });
        }

        /** 根据Worker在线快照刷新人数与左侧会员状态。 */
        function renderPresence(event) {
            conversationPanel.setOnline(event.memberIds, event.onlineCount);
        }

        /** 更新工作台头部实时连接状态。 */
        function updateRealtimeStatus(status) {
            chatHeader.setRealtimeStatus(status);
            if (status === 'disconnected' || status === 'error' || status === 'closed') {
                renderPresence({onlineCount: 0, memberIds: []});
            }
        }

        /** 收到客服领域事件后局部更新列表，并按消息ID增量补齐当前聊天内容。 */
        function handleRealtimeMessage(event) {
            if (!event) {
                return;
            }
            if (event.type === 'kefu.presence') {
                renderPresence(event);
                return;
            }
            if (event.type === 'kefu.read') {
                if (messageBoard.isCurrentConversation(event.conversationId)) {
                    messageBoard.setReadReceipt(event.lastReadMessageId);
                }
                return;
            }
            if (event.type === 'kefu.agent') {
                clearCachedConversation(Number(event.conversationId) || 0);
                if (Number(event.conversationId) === conversationId && historyDate === null) {
                    loadConversation(conversationId, true);
                }
                return;
            }
            if (event.type !== 'kefu.message') {
                return;
            }
            conversationPanel.moveToTop(event);
            if (conversationId && Number(event.conversationId) === conversationId && historyDate === null) {
                loadIncrementalMessages();
            }
        }

        /** 连接各板块回调。 */
        function connectCallbacks() {
            conversationPanel.setOnSelect(function (memberId, itemConversationId) {
                if (itemConversationId > 0) {
                    loadConversation(itemConversationId);
                    return;
                }
                initiateConversation(memberId);
            });
            conversationPanel.setOnSearchInput(function () {
                conversationPanel.render();
            });
            chatHeader.setOnClaim(function () { run(urls.claim); });
            chatHeader.setOnEnd(function () { run(urls.end); });
            messageComposer.setOnSend(sendMessage);
            messageComposer.setOnTransfer(function (targetUserId) {
                run(urls.transfer, {'TransferForm[targetUserId]': targetUserId});
            });
            messageBoard.setOnRetryFailed(submitOptimisticMessage);
            messageBoard.setOnAfterMarkRead(function (id) {
                conversationPanel.clearUnread(id);
            });
            historyList.setOnSelectDate(loadHistoryMessages);
        }

        /** 绑定页面级 document/window 监听。 */
        function bindPageEvents() {
            $(window).on('focus' + ns, messageBoard.markVisibleRead);
            $(document).on('visibilitychange' + ns, messageBoard.markVisibleRead);
            $(window).on('beforeunload' + ns, cleanup);
        }

        /** 解绑页面级 document/window 监听。 */
        function unbindPageEvents() {
            $(window).off(ns);
            $(document).off(ns);
        }

        /** 清理录音和实时连接。 */
        function cleanup() {
            messageComposer.cancelAudioRecording();
            if (realtimeConnection) {
                realtimeConnection.close();
            }
        }

        /** 连接实时通信。 */
        function connectRealtime() {
            realtimeConnection = websocket.connect({
                server: options.realtime.server,
                token: options.realtime.token,
                onStatus: updateRealtimeStatus,
                onMessage: handleRealtimeMessage
            });
        }

        /** 根据入口传入的会员ID自动打开或创建该会员的客服会话。 */
        function openInitialMember() {
            var memberId = Number(options.initialMemberId) || 0;
            if (!memberId) {
                return;
            }
            var item = (options.conversations || []).find(function (conversation) {
                return Number(conversation.memberId) === memberId;
            });
            if (!item) {
                ui.errorBox('会员不存在或已不在当前会员列表中。');
                return;
            }
            if (Number(item.conversationId) > 0) {
                loadConversation(Number(item.conversationId));
                return;
            }
            initiateConversation(memberId);
        }

        function init() {
            conversationPanel.init();
            chatHeader.init();
            messageBoard.init();
            messageComposer.init();
            detailTabs.init();
            memberDetail.init();
            historyList.init();
            floatingDrag.init();
            connectCallbacks();
            bindPageEvents();
            connectRealtime();
            openInitialMember();
        }

        function destroy() {
            cleanup();
            unbindPageEvents();
            floatingDrag.destroy();
            historyList.destroy();
            memberDetail.destroy();
            detailTabs.destroy();
            messageComposer.destroy();
            messageBoard.destroy();
            chatHeader.destroy();
            conversationPanel.destroy();
            conversationCache = {};
            conversationCacheOrder = [];
            $root.removeData(dataKey);
        }

        init();
        return { destroy: destroy };
    }

    /** 初始化一个客服工作台实例，重复调用会先销毁旧实例。 */
    exports.init = function (selector, options) {
        var $root = $(selector);
        if (!$root.length) {
            return null;
        }
        var current = $root.data(dataKey);
        if (current && typeof current.destroy === 'function') {
            current.destroy();
        }
        var page = new KefuWorkbenchPage($root, options || {});
        $root.data(dataKey, page);
        return page;
    };
});
