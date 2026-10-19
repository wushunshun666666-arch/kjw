import {CountdownPanel, ScratchCardPanel} from './portal-draw-interactions.js?v=20261019-3';
import {analysisValue, analysisCounts} from './portal-game-analysis.js?v=20261019-3';

/** Build text-only DOM for public draw data; never interpolate upstream HTML. */
function element(tag, text = '', className = '') {
    const node = document.createElement(tag);
    node.textContent = text;
    if (className) node.className = className;
    return node;
}

/** Preserve number order and apply racing's two-digit display. */
function ball(value, racing) {
    return element('span', racing ? String(value).padStart(2, '0') : String(value),
        `portal-ball${racing ? ` racing-ball racing-${value}` : ''}`);
}

/** Latest draw and collection status have a single rendering owner. */
class LatestDrawPanel {
    constructor(root) {
        this.root = root;
    }

    render(data, connectionError = false) {
        const latest = data.list[0];
        const racing = data.game.type === 'racing';
        this.root.querySelector('[data-region="issue"]').textContent = latest ? latest.issue : '--';
        this.root.title = latest ? `${data.game.name}　开奖时间：${latest.time}　最近采集：${data.collection.success_time || '暂无'}（北京时间）` : data.game.name;
        const numbers = [];
        if (latest) latest.balls.forEach((value, index) => {
            if (!racing && index > 0) numbers.push(element('span', '+', 'portal-current-symbol'));
            numbers.push(element('span', racing ? String(value).padStart(2, '0') : value, 'portal-current-number'));
        });
        this.root.querySelector('[data-region="balls"]').replaceChildren(...numbers);
        const summary = this.root.querySelector('[data-region="summary"]');
        summary.setAttribute('aria-label', latest ? `${data.game.sum_label} ${latest.sum} ${latest.size} ${latest.parity}` : data.game.sum_label);
        summary.replaceChildren(...(latest ? [
            element('span', racing ? data.game.sum_label : '=', racing ? 'portal-current-sum-label' : 'portal-current-symbol'),
            element('span', latest.sum, 'portal-current-sum'),
            element('span', latest.size, 'portal-current-size'),
            element('span', latest.parity, 'portal-current-parity'),
        ] : []));
        const status = connectionError ? '页面更新失败，正在重试；下方保留最近读取的数据。'
            : data.collection.unavailable ? '采集源暂未返回有效数据，正在重试。'
            : !latest ? '暂无已入库开奖记录，请等待采集。'
            : `最近采集：${data.collection.success_time || '暂无'}（北京时间）`;
        const statusNode = this.root.querySelector('[data-region="status"]');
        statusNode.textContent = status;
        statusNode.hidden = Boolean(latest) && !connectionError && !data.collection.unavailable;
    }
}

/** Results, total/category/position trends and frequencies share one data window. */
class DrawHistoryPanel {
    constructor(root, data) {
        this.root = root;
        this.data = data;
        this.tab = 'result';
        this.measureKey = 'sum';
        this.limit = 100;
        this.init();
    }

    init() {
        this.root.querySelectorAll('[data-tab]').forEach(button => {
            button.addEventListener('click', () => this.selectTab(button.dataset.tab));
            button.addEventListener('keydown', event => {
                const tabs = ['result', 'trend', 'statistics'];
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? 2
                    : (tabs.indexOf(this.tab) + (event.key === 'ArrowRight' ? 1 : 2)) % 3;
                this.selectTab(tabs[next]);
                this.root.querySelector(`[data-tab="${tabs[next]}"]`).focus();
            });
        });
        this.root.querySelectorAll('[data-analysis]').forEach(button => {
            button.addEventListener('click', () => this.selectAnalysis(button.dataset.analysis));
        });
        this.root.querySelector('[data-field="limit"]').addEventListener('change', event => {
            this.limit = Number(event.target.value);
            this.render();
        });
        this.selectTab('result');
    }

    selectTab(tab) {
        this.tab = tab;
        this.root.querySelectorAll('[data-tab]').forEach(button => {
            const active = button.dataset.tab === tab;
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
        });
        this.root.querySelector('[data-region="analysis-control"]').hidden = tab === 'result';
        this.root.querySelector('[role="tabpanel"]').setAttribute('aria-labelledby', `tab-${tab}`);
        this.render();
    }

    update(data) {
        this.data = data;
        this.render();
    }

    selectAnalysis(key) {
        this.measureKey = key;
        this.root.querySelectorAll('[data-analysis]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.analysis === key));
        });
        this.render();
    }

    render() {
        const rows = this.data.list.slice(0, this.limit);
        const target = this.root.querySelector('[data-region="table"]');
        this.root.querySelector('[data-region="samples"]').textContent = `实际 ${rows.length} 期`;
        if (!rows.length) {
            target.replaceChildren(element('p', '暂无开奖记录，采集成功后自动展示。', 'portal-empty'));
            return;
        }
        const table = element('table', '', `portal-data-table portal-${this.tab}`);
        const head = element('thead');
        const tr = element('tr');
        const measure = this.measure();
        const titles = this.tab === 'result' ? ['期号 / 开奖时间', '开奖号码', this.data.game.sum_label, '大小', '单双']
            : this.tab === 'trend' ? ['期号', measure.label, ...measure.values.map(String)] : [measure.label, '出现次数', '占比'];
        titles.forEach(title => { const th = element('th', title); th.scope = 'col'; tr.append(th); });
        head.append(tr);
        const body = element('tbody');
        if (this.tab === 'statistics') this.statistics(body, rows);
        else rows.forEach(row => this.drawRow(body, row));
        table.append(head, body);
        target.replaceChildren(table);
    }

    drawRow(body, row) {
        const tr = element('tr');
        const issue = element('td', row.issue, 'portal-issue-cell');
        if (this.tab === 'result') {
            issue.append(element('small', row.time));
            const numbers = element('td');
            const group = element('div', '', 'portal-balls');
            group.append(...row.balls.map(value => ball(value, this.data.game.type === 'racing')));
            numbers.append(group);
            tr.append(issue, numbers, element('td', row.sum), element('td', row.size), element('td', row.parity));
        } else {
            const measure = this.measure();
            const current = analysisValue(row, measure);
            tr.append(issue, element('td', current, 'portal-measure-value'));
            measure.values.forEach(value => {
                const td = element('td');
                if (current === value) td.append(this.marker(value, measure));
                else td.append(element('span', '·', 'portal-trend-dot'));
                tr.append(td);
            });
        }
        body.append(tr);
    }

    statistics(body, rows) {
        const measure = this.measure();
        analysisCounts(rows, measure).forEach(({value, count, percent}) => {
            const tr = element('tr');
            const number = element('td');
            number.append(this.marker(value, measure));
            const proportion = element('td');
            const bar = element('div', '', 'portal-frequency');
            const fill = element('span');
            fill.style.width = `${percent}%`;
            bar.append(fill);
            proportion.append(element('span', `${percent}%`), bar);
            tr.append(number, element('td', count), proportion);
            body.append(tr);
        });
    }

    measure() {
        return this.data.game.analysis.find(option => option.key === this.measureKey) || this.data.game.analysis[0];
    }

    marker(value, measure) {
        return ball(value, measure.field === 'balls' && this.data.game.type === 'racing');
    }
}

/** Advertisement controls only manage their own region. */
class AdvertisementPanel {
    constructor(root) {
        const button = root.querySelector('[data-action="ads"]');
        const ads = root.querySelector('[data-region="ads"]');
        button.addEventListener('click', () => {
            ads.hidden = !ads.hidden;
            button.textContent = ads.hidden ? '展开广告' : '收起广告';
            button.setAttribute('aria-expanded', String(!ads.hidden));
        });
    }
}

/** Poll only the local read endpoint, with overlap prevention and teardown. */
class GamePortal {
    constructor(root, data, url) {
        this.data = data;
        this.url = url;
        this.latest = new LatestDrawPanel(root.querySelector('.portal-current'));
        this.history = new DrawHistoryPanel(root, data);
        this.advertisement = new AdvertisementPanel(root);
        this.pending = false;
        this.destroyed = false;
        this.listeners = new AbortController();
        this.scratch = new ScratchCardPanel(root.querySelector('[data-action="scratch"]'), root.querySelector('#portal-scratch-dialog'), ball);
        this.countdown = new CountdownPanel(root.querySelector('[data-block="countdown"]'),
            text => this.scratch.setCountdown(text), () => this.refresh());
        this.init();
    }

    init() {
        this.latest.render(this.data);
        this.scratch.update(this.data);
        this.countdown.update(this.data.period);
        this.timer = setInterval(() => { if (!document.hidden) this.refresh(); }, 5000);
        document.addEventListener('visibilitychange', () => { if (!document.hidden) this.refresh(); }, {signal: this.listeners.signal});
        window.addEventListener('pagehide', () => this.destroy(), {once: true});
        window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
    }

    async refresh() {
        if (this.pending || this.destroyed) return;
        this.pending = true;
        this.request = new AbortController();
        const timeout = setTimeout(() => this.request.abort(), 12000);
        try {
            const response = await fetch(this.url, {signal: this.request.signal, cache: 'no-store', credentials: 'same-origin'});
            if (!response.ok) throw new Error('Request failed');
            const payload = await response.json();
            if (this.destroyed) return;
            if (payload.code !== 200 || payload.data.game.code !== this.data.game.code || !Array.isArray(payload.data.list)) throw new Error('Unexpected game data');
            this.data = payload.data;
            this.latest.render(this.data);
            this.history.update(this.data);
            this.scratch.update(this.data);
            this.countdown.update(this.data.period);
        } catch {
            if (!this.destroyed) this.latest.render(this.data, true);
        } finally {
            clearTimeout(timeout);
            this.pending = false;
        }
    }

    destroy() {
        this.destroyed = true;
        clearInterval(this.timer);
        this.request?.abort();
        this.listeners.abort();
        this.countdown.destroy();
        this.scratch.destroy();
    }
}

const root = document.getElementById('multi-portal');
if (root) new GamePortal(root, window.portalGameData, window.portalGameUrl);
