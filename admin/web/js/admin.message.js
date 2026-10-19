/**
 * 后台站内消息菜单和实时提醒模块。
 */
define(function (require, exports) {
    'use strict';

    var ui = require('admin.ui');
    var ajax = require('admin.ajax');
    var websocket = require('admin.websocket');

    /** 将消息创建时间格式化为相对时间。 */
    function timeFormat(timeString) {
        var time = Date.parse(String(timeString || '').replace(/-/g, '/')) / 1000;
        var seconds = Math.max(0, Math.floor(Date.now() / 1000 - time));
        var units = [
            [31536000, '年前'],
            [2592000, '月前'],
            [86400, '天前'],
            [3600, '小时前'],
            [60, '分钟前']
        ];
        for (var index = 0; index < units.length; index += 1) {
            var amount = Math.floor(seconds / units[index][0]);
            if (amount > 0) {
                return amount + ' ' + units[index][1];
            }
        }
        return seconds + ' 秒前';
    }

    /** 创建页面隐藏时使用的浏览器标题提醒器。 */
    function BrowserTitle() {
        var timerId = null;
        var originalTitle = document.title;

        /** 停止标题闪烁并恢复原始标题。 */
        function stop() {
            if (timerId !== null) {
                window.clearTimeout(timerId);
                timerId = null;
            }
            document.title = originalTitle;
        }

        /** 在页面隐藏时交替显示新消息标题。 */
        function flash(title, content) {
            stop();
            if (!document.hidden) {
                return;
            }
            function toggle() {
                document.title = document.title === originalTitle
                    ? '【' + String(title || '新消息') + '】' + String(content || '')
                    : originalTitle;
                timerId = window.setTimeout(toggle, 1000);
            }
            toggle();
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                stop();
            }
        });
        return {flash: flash, stop: stop};
    }

    /** 创建系统通知和浏览器标题提醒器。 */
    function MessageNotification() {
        var browserTitle = new BrowserTitle();

        /** 展示一条已经由服务端鉴权的站内消息提醒。 */
        function show(data) {
            if (window.Notification && window.Notification.permission === 'granted') {
                new window.Notification(String(data.title || '新消息'), {
                    body: String(data.content || '')
                });
            } else if (window.Notification && window.Notification.permission === 'default') {
                window.Notification.requestPermission();
            }
            browserTitle.flash(data.title, data.content);
        }
        return {show: show};
    }

    /** 创建站内消息顶部菜单。 */
    function MessageMenu(container, options) {
        var settings = $.extend(true, {
            icons: {'default': 'fa-envelope-o'}
        }, options || {});
        var $container = $(container);
        var $menu = $container.find('.message-list');
        var $badge = $container.find('.message-badge');
        var $header = $container.find('.message-header');

        /** 更新顶部未读数量。 */
        function updateNewNums(number) {
            var count = Math.max(0, parseInt(number, 10) || 0);
            $badge.text(count > 0 ? count : '');
            $header.find('span').text(count);
        }

        /** 从数据库接口刷新当前用户未读数量。 */
        function refreshNewNums() {
            ajax.get(settings.urls.getNewNums, function (response) {
                if (response.success()) {
                    updateNewNums(response.data().nums);
                }
            });
        }

        /** 标记一条消息已读并同步未读数量。 */
        function read(item, success) {
            ajax.post(settings.urls.read, {id: item.id}, function (response) {
                if (!response.success()) {
                    return;
                }
                if (typeof success === 'function') {
                    success();
                }
                updateNewNums(response.data().nums);
            });
        }

        /** 使用安全文本节点渲染顶部消息菜单。 */
        function renderItems(items) {
            $menu.empty();
            $.each(items || [], function (index, item) {
                var icon = settings.icons[item.typeId] || settings.icons['default'];
                var time = item.isRead
                    ? String(item.createTime || '').substring(0, 10)
                    : timeFormat(item.createTime);
                var $row = $('<li>').toggleClass('msg-un-read', !item.isRead);
                var $link = $('<a>').addClass('dropdown-item').attr('href', '#');
                $link.append($('<div>').addClass('float-start').append($('<i>').addClass('fa ' + icon)));
                $link.append($('<h4>').text(String(item.title || '')).append($('<small>').text(time)));
                $link.append($('<p>').text(String(item.content || '')));
                $row.append($link).on('click', function (event) {
                    event.preventDefault();
                    read(item, function () {
                        $row.removeClass('msg-un-read');
                    });
                });
                $menu.append($row);
            });
        }

        /** 从数据库接口加载最近十条站内消息。 */
        function load() {
            ajax.get(settings.urls.getList, function (response) {
                if (response.success()) {
                    var data = response.data();
                    renderItems(data.items);
                    updateNewNums(data.nums);
                }
            });
        }

        /** 将当前用户的全部消息标记为已读。 */
        function markAllRead(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            ajax.post(settings.urls.markAllRead, {}, function (response) {
                if (response.success()) {
                    $menu.find('.msg-un-read').removeClass('msg-un-read');
                    updateNewNums(0);
                }
            });
        }

        $container.on('show.bs.dropdown', load);
        $container.on('click', '.mark-all-read', markAllRead);
        $container.on('click', '.message-index', function (event) {
            event.preventDefault();
            ui.navigate(settings.urls.index);
        });
        refreshNewNums();

        return {
            refreshNewNums: refreshNewNums,
            updateNewNums: updateNewNums
        };
    }

    /** 初始化顶部菜单和只接收后台站内消息事件的WebSocket连接。 */
    exports.init = function (container, options) {
        var messageMenu = new MessageMenu(container, options);
        var notification = new MessageNotification();
        websocket.connect({
            server: options.push.server,
            token: options.push.token,
            onMessage: function (data) {
                if (data.type === 'admin.message') {
                    notification.show(data);
                    messageMenu.refreshNewNums();
                }
            }
        });
        top._messageMenu = messageMenu;
    };
});
