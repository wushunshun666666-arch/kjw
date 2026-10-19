/**
 * 代理推广关系卡片树。
 *
 * 子节点按需请求，已加载节点仅在页面内展开或收起，不重复访问后台。
 */
define(function (require, exports) {
    var ajax = require('admin.ajax');
    var ui = require('admin.ui');

    /**
     * 将任意值转换成安全字符串。
     */
    function text(value) {
        return value === null || value === undefined ? '' : String(value);
    }

    /**
     * 对接口金额做紧凑展示，完整数值仍保留在标题中。
     */
    function formatAmount(value) {
        var amount = Number(value);
        if (!Number.isFinite(amount)) {
            return '0.0000';
        }
        if (Math.abs(amount) >= 10000) {
            return '¥' + (amount / 10000).toFixed(amount % 10000 === 0 ? 0 : 1) + '万';
        }
        return '¥' + amount.toFixed(2);
    }

    /**
     * 创建头像；图片加载失败时回退到姓名首字。
     */
    function createAvatar(item) {
        var fallback = text(item.displayName).trim().charAt(0) || '会';
        if (!item.avatar) {
            return $('<span>', {'class': 'member-agent-avatar'}).text(fallback);
        }

        var $avatar = $('<img>', {
            'class': 'member-agent-avatar',
            alt: text(item.displayName),
            src: text(item.avatar)
        });
        $avatar.on('error', function () {
            $(this).replaceWith($('<span>', {'class': 'member-agent-avatar'}).text(fallback));
        });
        return $avatar;
    }

    /**
     * 创建一个统计格。
     */
    function createMetric(label, value, title) {
        return $('<span>', {'class': 'member-agent-node-metric'})
            .append($('<span>', {'class': 'member-agent-node-metric-label'}).text(label))
            .append($('<strong>', {
                'class': 'member-agent-node-metric-value',
                title: title || text(value)
            }).text(value));
    }

    /**
     * 创建会员卡片，所有接口字段均通过text写入，避免拼接不可信HTML。
     */
    function createCard(item) {
        var hasChildren = Boolean(item.hasChildren);
        var frozen = Boolean(item.isFrozen);
        var $card = $('<div>', {
            'class': 'member-agent-node-card' + (hasChildren ? ' has-children' : '') + (frozen ? ' is-frozen' : ''),
            'data-member-id': Number(item.id),
            role: hasChildren ? 'button' : 'group',
            tabindex: hasChildren ? 0 : -1,
            'aria-expanded': hasChildren ? 'false' : null
        });
        var $head = $('<div>', {'class': 'member-agent-node-head'});
        var $identity = $('<span>', {'class': 'member-agent-node-identity'})
            .append($('<span>', {'class': 'member-agent-node-name', title: text(item.displayName)}).text(item.displayName))
            .append($('<span>', {'class': 'member-agent-node-uid'}).text('UID ' + text(item.id)));

        $head.append(createAvatar(item)).append($identity);
        if (hasChildren) {
            $head.append(
                $('<span>', {'class': 'member-agent-toggle', 'aria-hidden': 'true'})
                    .append($('<i>', {'class': 'fa fa-chevron-down'}))
            );
        }

        var amountText = formatAmount(item.availableAmount);
        var $metrics = $('<div>', {'class': 'member-agent-node-metrics'})
            .append(createMetric('直属', text(item.directChildCount)))
            .append(createMetric('团队', text(item.teamMemberCount)))
            .append(createMetric('余额', amountText, '¥' + text(item.availableAmount)));
        var $status = $('<span>', {
            'class': 'member-agent-status' + (frozen ? ' is-frozen' : '')
        }).text(text(item.statusName) || (frozen ? '冻结' : '正常'));
        var $footer = $('<div>', {'class': 'member-agent-node-footer'})
            .append($('<span>', {
                'class': 'member-agent-node-code',
                title: '推广码：' + text(item.inviteCode)
            }).text('推广码 ' + text(item.inviteCode)))
            .append($status);

        return $card.append($head, $metrics, $footer);
    }

    /**
     * 创建树枝容器。
     */
    function createBranch(item) {
        return $('<div>', {
            'class': 'member-agent-branch',
            'data-branch-id': Number(item.id)
        }).append(createCard(item));
    }

    /**
     * 按当前可见卡片位置重绘父子连接线。
     */
    function drawLines($root) {
        var $canvas = $root.find('.member-agent-tree-canvas');
        var canvas = $canvas.get(0);
        var svg = $root.find('.member-agent-tree-lines').get(0);
        if (!canvas || !svg) {
            return;
        }

        var canvasRect = canvas.getBoundingClientRect();
        var width = Math.max(canvas.scrollWidth, canvas.clientWidth);
        var height = Math.max(canvas.scrollHeight, canvas.clientHeight);
        svg.setAttribute('width', width);
        svg.setAttribute('height', height);
        svg.setAttribute('viewBox', '0 0 ' + width + ' ' + height);
        while (svg.firstChild) {
            svg.removeChild(svg.firstChild);
        }

        $root.find('.member-agent-branch').each(function () {
            var $branch = $(this);
            var $children = $branch.children('.member-agent-children:visible');
            var parentCard = $branch.children('.member-agent-node-card').get(0);
            if (!parentCard || !$children.length) {
                return;
            }

            var parentRect = parentCard.getBoundingClientRect();
            var startX = parentRect.left - canvasRect.left + parentRect.width / 2;
            var startY = parentRect.bottom - canvasRect.top;
            $children.children('.member-agent-branch').each(function () {
                var childCard = $(this).children('.member-agent-node-card').get(0);
                if (!childCard) {
                    return;
                }
                var childRect = childCard.getBoundingClientRect();
                var endX = childRect.left - canvasRect.left + childRect.width / 2;
                var endY = childRect.top - canvasRect.top;
                var middleY = startY + (endY - startY) / 2;
                var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', 'M ' + startX + ' ' + startY + ' V ' + middleY + ' H ' + endX + ' V ' + endY);
                svg.appendChild(path);
            });
        });
    }

    /**
     * 在浏览器下一次绘制前更新连线，合并短时间内的重复重绘请求。
     */
    function scheduleDraw($root) {
        var state = $root.data('agentTreeState');
        if (!state || state.frameId) {
            return;
        }
        state.frameId = window.requestAnimationFrame(function () {
            state.frameId = null;
            drawLines($root);
        });
    }

    /**
     * 请求指定节点的直属下级。
     */
    function loadChildren($root, memberId, done) {
        var config = $root.data('agentTreeConfig');
        var separator = config.childrenUrl.indexOf('?') === -1 ? '?' : '&';
        var url = config.childrenUrl + separator + 'node=' + encodeURIComponent(memberId);
        ajax.get(url, function (result) {
            if (!result.success()) {
                ui.alertError(result.message() || '推广关系加载失败。');
                done(null);
                return;
            }
            var data = result.data() || {};
            done(Array.isArray(data.items) ? data.items : []);
        }, function (result) {
            ui.alertError(result.message() || '推广关系加载失败。');
            done(null);
        });
    }

    /**
     * 展开或收起会员的直属下级。
     */
    function toggleBranch($root, $card) {
        if (!$card.hasClass('has-children') || $card.hasClass('is-loading')) {
            return;
        }

        var $branch = $card.parent('.member-agent-branch');
        var $children = $branch.children('.member-agent-children');
        if ($children.length) {
            var expanded = !$children.is(':visible');
            $children.toggle(expanded);
            $card.toggleClass('is-expanded', expanded).attr('aria-expanded', expanded ? 'true' : 'false');
            scheduleDraw($root);
            return;
        }

        $card.addClass('is-loading');
        $card.find('.member-agent-toggle i').attr('class', 'fa fa-spinner fa-spin');
        loadChildren($root, $card.data('memberId'), function (items) {
            $card.removeClass('is-loading');
            $card.find('.member-agent-toggle i').attr('class', 'fa fa-chevron-down');
            if (items === null) {
                return;
            }
            if (!items.length) {
                $card.removeClass('has-children').removeAttr('aria-expanded tabindex role');
                $card.find('.member-agent-toggle').remove();
                scheduleDraw($root);
                return;
            }

            $children = $('<div>', {'class': 'member-agent-children'});
            $.each(items, function (_, item) {
                $children.append(createBranch(item));
            });
            $branch.append($children);
            $card.addClass('is-expanded').attr('aria-expanded', 'true');
            scheduleDraw($root);
        });
    }

    /**
     * 初始化代理卡片树。
     */
    exports.init = function (selector) {
        var $root = $(selector).first();
        if (!$root.length || $root.data('agentTreeInitialized')) {
            return;
        }

        var config;
        try {
            config = JSON.parse($root.attr('data-config') || '{}');
        } catch (error) {
            ui.alertError('代理树页面配置无效。');
            return;
        }
        if (!config.childrenUrl) {
            ui.alertError('代理树接口地址未配置。');
            return;
        }

        $root.data('agentTreeInitialized', true);
        $root.data('agentTreeConfig', config);
        $root.data('agentTreeState', {frameId: null});
        $root.on('click', '.member-agent-node-card.has-children', function () {
            toggleBranch($root, $(this));
        });
        $root.on('keydown', '.member-agent-node-card.has-children', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleBranch($root, $(this));
            }
        });
        $(window).on('resize.memberAgentTree', function () {
            scheduleDraw($root);
        });

        loadChildren($root, 0, function (items) {
            var $state = $root.find('.member-agent-tree-state');
            if (items === null) {
                $state.removeClass('d-none').addClass('is-error')
                    .text('推广关系加载失败，请刷新页面后重试。');
                return;
            }
            if (!items.length) {
                $state.removeClass('d-none').text('暂无推广关系数据。');
                return;
            }

            var $forest = $root.find('.member-agent-tree-forest').empty();
            $.each(items, function (_, item) {
                $forest.append(createBranch(item));
            });
            $state.addClass('d-none');
            scheduleDraw($root);
        });
    };
});
