import {CountdownPanel, ScratchCardPanel} from './portal-draw-interactions.js?v=20261103-3';
import {analysisValue, analysisCounts} from './portal-game-analysis.js?v=20261103-3';

/** Build text-only DOM for public draw data; never interpolate upstream HTML. */
function element(tag, text = '', className = '') {
    const node = document.createElement(tag);
    node.textContent = text;
    if (className) node.className = className;
    return node;
}

/** Preserve number order and apply racing's two-digit display. */
function ball(value, racing, marksix = false, attributes = null) {
    const node = element('span', racing || marksix ? String(value).padStart(2, '0') : String(value),
        `portal-ball${racing ? ` racing-ball racing-${value}` : ''}`);
    if (marksix && attributes) decorate(node, value, attributes);
    return node;
}

function waveClass(wave) { return ({'红波': 'wave-red', '蓝波': 'wave-blue', '绿波': 'wave-green'})[wave] || ''; }
function attributesAt(row, index) { return row.marksix ? {zodiac: row.marksix.zodiacs[index], wave: row.marksix.waves[index]} : null; }
function decorate(node, value, attributes) {
    const color = waveClass(attributes.wave);
    if (color) node.classList.add(color);
    node.setAttribute('aria-label', `${String(value).padStart(2, '0')} ${attributes.zodiac} ${attributes.wave}`);
}
function stackBall(node, attributes) {
    if (!attributes) return node;
    const stack = element('span', '', 'portal-marksix-stack');
    stack.append(node, element('small', attributes.zodiac, 'portal-zodiac'));
    return stack;
}

/** Latest draw and collection status have a single rendering owner. */
class LatestDrawPanel {
    constructor(root) {
        this.root = root;
    }

    render(data, connectionError = false) {
        const latest = data.list[0];
        const racing = data.game.type === 'racing';
        const marksix = data.game.type === 'marksix';
        this.root.querySelector('[data-region="issue"]').textContent = latest ? latest.issue : '--';
        this.root.title = latest ? `${data.game.name}　开奖时间：${latest.time}　最近采集：${data.collection.success_time || '暂无'}（北京时间）` : data.game.name;
        const numbers = [];
        if (latest) latest.balls.forEach((value, index) => {
            if (!racing && !marksix && index > 0) numbers.push(element('span', '+', 'portal-current-symbol'));
            const number = element('span', racing || marksix ? String(value).padStart(2, '0') : value, 'portal-current-number');
            const attributes = marksix ? attributesAt(latest, index) : null;
            if (attributes) decorate(number, value, attributes);
            if (marksix) number.title = data.game.positions[index];
            const shown = stackBall(number, attributes);
            if (marksix && index === 6) {
                const special = element('span', '', 'portal-current-special');
                special.append(element('span', '特码', 'portal-special-label'), shown);
                numbers.push(special);
            } else numbers.push(shown);
        });
        const balls = this.root.querySelector('[data-region="balls"]');
        balls.classList.toggle('portal-current-racing', racing);
        balls.classList.toggle('portal-current-marksix', marksix);
        balls.replaceChildren(...numbers);
        const summary = this.root.querySelector('[data-region="summary"]');
        summary.setAttribute('aria-label', latest ? `${data.game.sum_label} ${latest.sum} ${latest.size} ${latest.parity}` : data.game.sum_label);
        summary.replaceChildren(...(latest ? [
            element('span', racing ? data.game.sum_label : '=', racing ? 'portal-current-sum-label' : 'portal-current-symbol'),
            element('span', latest.sum, 'portal-current-sum'),
            element('span', latest.size, 'portal-current-size'),
            element('span', latest.parity, 'portal-current-parity'),
        ] : []));
        let specialInfo = this.root.querySelector('[data-region="marksix-special"]');
        if (!specialInfo) {
            specialInfo = element('div', '', 'portal-special-info');
            specialInfo.dataset.region = 'marksix-special';
            this.root.querySelector('.portal-current-result').append(specialInfo);
        }
        const special = marksix ? latest?.marksix?.special : null;
        specialInfo.hidden = !special;
        specialInfo.replaceChildren(...(special ? [element('strong', `特码 ${String(special.number).padStart(2, '0')}`),
            element('span', `生肖 ${special.zodiac}`), element('span', special.wave, waveClass(special.wave) + ' portal-classification'),
            element('span', `大小 ${special.size}`), element('span', `单双 ${special.parity}`), element('span', `组合 ${special.combo}`)] : []));
        const status = connectionError ? '页面更新失败，正在重试；下方保留最近读取的数据。'
            : data.collection.unavailable ? '采集源暂未返回有效数据，正在重试。'
            : data.collection.delayed ? '开奖数据待更新，可能处于维护时段；当前保留最近开奖记录。'
            : !latest ? '暂无已入库开奖记录，请等待采集。'
            : `最近采集：${data.collection.success_time || '暂无'}（北京时间）`;
        const statusNode = this.root.querySelector('[data-region="status"]');
        statusNode.textContent = status;
        statusNode.hidden = Boolean(latest) && !connectionError && !data.collection.unavailable && !data.collection.delayed;
    }
}

/** Results, total/category/position trends and frequencies share one data window. */
class DrawHistoryPanel {
    constructor(root, data) {
        this.root = root;
        this.data = data;
        this.tab = 'result';
        this.measureKey = 'sum';
        this.listeners = new AbortController();
        this.init();
    }

    init() {
        const options = {signal: this.listeners.signal};
        this.root.querySelectorAll('[data-tab]').forEach(button => {
            button.addEventListener('click', () => this.selectTab(button.dataset.tab), options);
            button.addEventListener('keydown', event => {
                const tabs = ['result', 'trend', 'statistics'];
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? 2
                    : (tabs.indexOf(this.tab) + (event.key === 'ArrowRight' ? 1 : 2)) % 3;
                this.selectTab(tabs[next]);
                this.root.querySelector(`[data-tab="${tabs[next]}"]`).focus();
            }, options);
        });
        this.root.querySelector('[data-region="analysis-control"]').addEventListener('click', event => {
            const button = event.target.closest('[data-analysis]');
            if (button) this.selectAnalysis(button.dataset.analysis);
        }, options);
        this.buildAnalysis();
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
        const changed = this.data.game.code !== data.game.code;
        this.data = data;
        if (changed) this.buildAnalysis();
        this.render();
    }

    buildAnalysis() {
        if (!this.data.game.analysis.some(option => option.key === this.measureKey)) this.measureKey = 'sum';
        const controls = this.root.querySelector('[data-region="analysis-control"]');
        controls.replaceChildren(element('span', '分析项目', 'portal-analysis-label'), ...this.data.game.analysis.map(option => {
            const button = element('button', option.label);
            button.type = 'button';
            button.dataset.analysis = option.key;
            button.setAttribute('aria-controls', 'portal-game-panel');
            button.setAttribute('aria-pressed', String(option.key === this.measureKey));
            return button;
        }));
    }

    destroy() { this.listeners.abort(); }

    selectAnalysis(key) {
        this.measureKey = key;
        this.root.querySelectorAll('[data-analysis]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.analysis === key));
        });
        this.render();
    }

    render() {
        const rows = this.data.list;
        const target = this.root.querySelector('[data-region="table"]');
        this.root.querySelector('[data-region="samples"]').textContent = `本页实际 ${rows.length} 期`;
        if (!rows.length) {
            target.replaceChildren(element('p', '暂无开奖记录，采集成功后自动展示。', 'portal-empty'));
            return;
        }
        const table = element('table', '', `portal-data-table portal-${this.tab}`);
        const head = element('thead');
        const tr = element('tr');
        const measure = this.measure();
        const titles = this.tab === 'result' ? (this.data.game.type === 'marksix'
            ? ['期号 / 开奖时间', '正码 / 特码', '特码生肖', '特码波色', '特码大小', '特码单双', '特码组合', '七球总和', '总和大小', '总和单双']
            : ['期号 / 开奖时间', '开奖号码', this.data.game.sum_label, '大小', '单双'])
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
            row.balls.forEach((value, index) => {
                if (this.data.game.type === 'marksix' && index === 6) group.append(element('span', '特码', 'portal-special-label'));
                const attributes = this.data.game.type === 'marksix' ? attributesAt(row, index) : null;
                const number = ball(value, this.data.game.type === 'racing', this.data.game.type === 'marksix', attributes);
                group.append(stackBall(number, attributes));
            });
            numbers.append(group);
            tr.append(issue, numbers);
            if (this.data.game.type === 'marksix') {
                const special = row.marksix?.special;
                ['zodiac', 'wave', 'size', 'parity', 'combo'].forEach(key => tr.append(element('td', special?.[key] ?? '-', key === 'wave' ? waveClass(special?.wave) + ' portal-classification' : '')));
            }
            tr.append(element('td', row.sum), element('td', row.size), element('td', row.parity));
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
        if (measure.field === 'marksix_special') return element('span', value === '-' ? '未知' : value, 'portal-measure-badge ' + (measure.attribute === 'wave' ? waveClass(value) : ''));
        return ball(value, measure.field === 'balls' && this.data.game.type === 'racing', measure.field === 'balls' && this.data.game.type === 'marksix');
    }
}

/** A single mounted draw widget; navigation owns fetching and polling. */
export class GamePortal {
    constructor(currentRoot, historyRoot, data, onExpire) {
        this.currentRoot = currentRoot;
        this.historyRoot = historyRoot;
        this.latest = new LatestDrawPanel(currentRoot.querySelector('.portal-current'));
        this.history = new DrawHistoryPanel(historyRoot, {...data, list: []});
        this.scratch = new ScratchCardPanel(currentRoot.querySelector('[data-action="scratch"]'), historyRoot.querySelector('#portal-scratch-dialog'), ball);
        this.countdown = new CountdownPanel(currentRoot.querySelector('[data-block="countdown"]'),
            text => this.scratch.setCountdown(text), onExpire);
        this.update(data);
    }

    update(data) {
        if (!this.data || this.data.game.code !== data.game.code) this.history.update({...data, list: []});
        this.data = data;
        this.latest.render(data);
        this.historyRoot.querySelector('#portal-scratch-title').textContent = `${data.game.name}咪牌`;
        this.historyRoot.querySelector('.portal-rules').textContent = `${data.game.rule}走势与统计使用当前页的实际开奖记录。`;
        this.scratch.update(data);
        this.countdown.update(data.period);
    }

    /** Historical page changes never replace the live draw or its countdown. */
    updateHistory(list) {
        this.history.update({...this.data, list});
    }

    destroy() {
        this.history.destroy();
        this.countdown.destroy();
        this.scratch.destroy();
    }
}
