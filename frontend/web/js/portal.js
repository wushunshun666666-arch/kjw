(function () {
    'use strict';
    /** 显示北京时间，不推测下一期开奖。 */
    function BeijingClock(element) {
        var timer;
        var formatter = new Intl.DateTimeFormat('zh-CN', {
            timeZone: 'Asia/Shanghai', year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
        });
        function update() { element.textContent = formatter.format(new Date()); }
        function init() { update(); timer = window.setInterval(update, 1000); }
        init();
        return {destroy: function () { window.clearInterval(timer); }};
    }
    /** 更新已渲染开奖卡片，保留查询导航和原始服务器数据。 */
    function ResultPanel(root) {
        var timer;
        var pending = false;
        var controller = null;
        function update(game) {
            var cards = root.querySelectorAll('[data-game]');
            cards.forEach(function (card) {
                if (card.dataset.game !== game.code || !game.draw) { return; }
                var issue = card.querySelector('[data-field="issue"]');
                if (!issue || issue.textContent === game.draw.issue) { return; }
                issue.textContent = game.draw.issue;
                card.querySelector('[data-field="time"]').textContent = game.draw.time;
                card.querySelector('[data-field="total"]').textContent = game.draw.total;
                card.querySelector('[data-field="source"]').textContent = '来源：' + game.draw.source;
                var balls = card.querySelector('[data-field="balls"]');
                balls.replaceChildren();
                game.draw.balls.forEach(function (number) {
                    var ball = document.createElement('span');
                    ball.className = 'number-ball';
                    ball.textContent = number;
                    balls.appendChild(ball);
                });
                card.querySelector('.status-pill').textContent = '结果已更新';
            });
        }
        function refresh() {
            if (pending || document.hidden) { return; }
            pending = true;
            controller = new AbortController();
            var timeout = window.setTimeout(function () { controller.abort(); }, 10000);
            fetch(root.dataset.refreshUrl, {signal: controller.signal, headers: {'Accept': 'application/json'}})
                .then(function (response) { if (!response.ok) { throw new Error('unavailable'); } return response.json(); })
                .then(function (data) { if (Array.isArray(data.games)) { data.games.forEach(update); } })
                .catch(function () { /* 保留已加载结果及其原始开奖时间。 */ })
                .finally(function () { pending = false; window.clearTimeout(timeout); });
        }
        function init() { timer = window.setInterval(refresh, 30000); }
        init();
        return {destroy: function () { window.clearInterval(timer); if (controller) { controller.abort(); } }};
    }
    var results = document.querySelector('[data-refresh-url]');
    if (results) {
        var panel = new ResultPanel(results);
        window.addEventListener('pagehide', function (event) { if (!event.persisted) { panel.destroy(); } });
    }
    var element = document.getElementById('beijing-clock');
    if (element) {
        var clock = new BeijingClock(element);
        window.addEventListener('pagehide', function (event) { if (!event.persisted) { clock.destroy(); } });
    }
}());
