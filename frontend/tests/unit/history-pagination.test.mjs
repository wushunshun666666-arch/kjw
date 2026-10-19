import {readFile} from 'node:fs/promises';
import {test} from 'node:test';
import assert from 'node:assert/strict';

const source = await readFile(new URL('../../web/js/portal-history-pagination.js', import.meta.url), 'utf8');
const {HistoryPagination} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const settle = () => new Promise(resolve => setImmediate(resolve));

function fixture({abortable = false} = {}) {
    const listeners = new Map();
    const nodes = {
        '[data-history-pagination]': Array.from({length: 2}, () => ({setAttribute(name, value) { this[name] = value; }})),
        '[data-page-action]': Array.from({length: 2}, () => ['first', 'previous', 'next'].map(action => ({dataset: {pageAction: action}}))).flat(),
        '[data-page-size]': Array.from({length: 3}, () => ({value: '20'})),
        '[data-page-status]': [{}, {}],
        '[data-page-error]': [{}, {}],
    };
    const root = {
        addEventListener(type, listener) { listeners.set(type, listener); },
        removeEventListener(type, listener) { if (listeners.get(type) === listener) listeners.delete(type); },
        querySelectorAll(selector) { return nodes[selector] || []; },
        contains(node) { return Object.values(nodes).flat().includes(node); },
    };
    const timeouts = new Map();
    let timerId = 0;
    const timers = {
        setTimeout(callback, delay) { timeouts.set(++timerId, {callback, delay}); return timerId; },
        clearTimeout(id) { timeouts.delete(id); },
    };
    const requests = [];
    const rendered = [];
    const pagination = new HistoryPagination({
        root, endpoint: '/index.php?r=reference%2Fhistory', urlbase: 'https://example.test/index.php?r=site%2Findex#/',
        onPage: data => rendered.push(data),
        environment: {timers, fetch: (url, options) => new Promise((resolve, reject) => {
            requests.push({url, options, resolve, reject});
            if (abortable) options.signal.addEventListener('abort', () => reject(new Error('aborted')), {once: true});
        })},
    });
    return {
        pagination, nodes, listeners, requests, rendered, timeouts,
        response(index, changes = {}) {
            const params = new URL(requests[index].url).searchParams;
            const pageSize = Number(params.get('page_size'));
            const data = {
                game: {code: params.get('game')}, list: [{issue: '100'}],
                pagination: {page: Number(params.get('page')), page_size: pageSize, total: 240, total_pages: Math.ceil(240 / pageSize),
                    snapshot: Number(params.get('snapshot')) || 1000},
                ...changes,
            };
            requests[index].resolve({ok: true, json: async () => ({code: 200, data})});
            return data;
        },
        params(index) { return Object.fromEntries(new URL(requests[index].url).searchParams); },
        click(action, group = 0) {
            const button = nodes['[data-page-action]'][group * 3 + ['first', 'previous', 'next'].indexOf(action)];
            return listeners.get('click')?.({target: {closest: () => button}, preventDefault() {}});
        },
        size(value, index = 0) {
            const select = nodes['[data-page-size]'][index];
            select.value = String(value);
            return listeners.get('change')?.({target: {closest: () => select}});
        },
    };
}

async function firstPage(f, code = 'jnd28') {
    const result = f.pagination.selectGame(code);
    f.pagination.observeLatest(code, '100');
    f.response(f.requests.length - 1);
    await result;
}

test('construction sends no request; initial selection and first observation request one real snapshot', async () => {
    const f = fixture();
    assert.equal(f.requests.length, 0);
    assert.ok(f.nodes['[data-page-action]'].every(button => button.disabled));
    await firstPage(f);
    assert.deepEqual(f.params(0), {r: 'reference/history', game: 'jnd28', page: '1', page_size: '20', snapshot: '0'});
    assert.equal(f.requests.length, 1);
    assert.equal(f.rendered.length, 1);
    assert.equal(f.nodes['[data-page-status]'][0].textContent, '第 1/12 页，共 240 期');
    f.pagination.selectGame('jnd28');
    assert.equal(f.requests.length, 1);
    f.pagination.destroy();
});

test('next and previous retain the database snapshot and render only after success', async () => {
    const f = fixture();
    await firstPage(f);
    const next = f.click('next', 1);
    assert.equal(f.params(1).snapshot, '1000');
    assert.equal(f.params(1).page, '2');
    assert.equal(f.rendered.length, 1);
    assert.ok(f.nodes['[data-page-action]'].every(button => button.disabled));
    f.click('next');
    assert.equal(f.requests.length, 2);
    f.response(1);
    await next;
    assert.equal(f.pagination.pagination.page, 2);
    const previous = f.click('previous');
    assert.equal(f.params(2).snapshot, '1000');
    f.response(2);
    await previous;
    assert.equal(f.pagination.pagination.page, 1);
    f.pagination.go(0);
    f.pagination.go(13);
    f.pagination.go(1);
    assert.equal(f.requests.length, 3);
    f.pagination.destroy();
});

test('last-page boundaries and empty databases disable next without inventing pages', async () => {
    const f = fixture();
    await firstPage(f);
    const last = f.pagination.go(12);
    f.response(1);
    await last;
    assert.equal(f.nodes['[data-page-action]'][2].disabled, true);
    f.click('next');
    assert.equal(f.requests.length, 2);
    const empty = f.pagination.selectGame('az51');
    f.response(2, {list: [], pagination: {page: 1, page_size: 20, total: 0, total_pages: 1, snapshot: 0}});
    await empty;
    assert.equal(f.nodes['[data-page-status]'][0].textContent, '第 1/1 页，共 0 期');
    assert.equal(f.nodes['[data-page-action]'][1].disabled, true);
    assert.equal(f.nodes['[data-page-action]'][2].disabled, true);
    assert.equal(f.nodes['[data-page-action]'][0].disabled, false);
    f.pagination.destroy();
});

test('first button obtains a fresh snapshot even while already on the first page', async () => {
    const f = fixture();
    await firstPage(f);
    const latest = f.click('first');
    assert.equal(f.params(1).snapshot, '0');
    f.response(1, {pagination: {page: 1, page_size: 20, total: 241, total_pages: 13, snapshot: 1001}});
    await latest;
    const next = f.click('next');
    assert.equal(f.params(2).snapshot, '1001');
    f.response(2);
    await next;
    f.pagination.destroy();
});

test('page-size changes synchronize every selector and start a fresh first page', async () => {
    const f = fixture();
    await firstPage(f);
    const size = f.size(50, 2);
    assert.equal(f.params(1).page_size, '50');
    assert.equal(f.params(1).page, '1');
    assert.equal(f.params(1).snapshot, '0');
    assert.ok(f.nodes['[data-page-size]'].every(select => select.value === '50' && select.disabled));
    f.response(1);
    await size;
    assert.ok(f.nodes['[data-page-size]'].every(select => select.value === '50' && !select.disabled));
    assert.equal(f.pagination.pagination.total_pages, 5);
    f.size(10);
    assert.ok(f.nodes['[data-page-size]'].every(select => select.value === '50'));
    assert.equal(f.requests.length, 2);
    f.pagination.destroy();
});

test('failed page and size requests preserve rows, page, snapshot and selected size, then permit retry', async () => {
    const f = fixture();
    await firstPage(f);
    const saved = {...f.pagination.pagination};
    const next = f.click('next');
    f.requests[1].reject(new Error('offline'));
    await next;
    assert.deepEqual(f.pagination.pagination, saved);
    assert.equal(f.rendered.length, 1);
    assert.equal(f.nodes['[data-page-error]'][0].hidden, false);
    const size = f.size(50);
    f.requests[2].reject(new Error('offline'));
    await size;
    assert.deepEqual(f.pagination.pagination, saved);
    assert.ok(f.nodes['[data-page-size]'].every(select => select.value === '20'));
    const retry = f.click('next');
    f.response(3);
    await retry;
    assert.equal(f.pagination.pagination.page, 2);
    assert.ok(f.nodes['[data-page-error]'].every(error => error.hidden));
    f.pagination.destroy();
});

test('changing games interrupts pending requests and ignores late transport responses', async () => {
    const f = fixture();
    const first = f.pagination.selectGame('jnd28');
    const last = f.pagination.selectGame('az10');
    assert.equal(f.requests[0].options.signal.aborted, true);
    assert.equal(f.params(1).page, '1');
    assert.equal(f.params(1).snapshot, '0');
    f.response(1);
    await last;
    f.response(0);
    await first;
    assert.deepEqual(f.rendered.map(data => data.game.code), ['az10']);
    assert.equal(f.timeouts.size, 0);
    f.pagination.destroy();
});

test('live polling never replaces a historical page or a pending request for one', async () => {
    const f = fixture();
    await firstPage(f);
    const next = f.click('next');
    f.pagination.observeLatest('jnd28', '101');
    f.response(1);
    await next;
    f.pagination.observeLatest('jnd28', '102');
    f.pagination.observeLatest('az10', '999');
    assert.equal(f.requests.length, 2);
    assert.equal(f.pagination.pagination.page, 2);
    const previous = f.click('previous');
    f.response(2);
    await previous;
    assert.equal(f.params(2).snapshot, '1000');
    const latest = f.pagination.observeLatest('jnd28', '103');
    assert.equal(f.params(3).snapshot, '0');
    f.response(3, {list: [{period: '103'}]});
    await latest;
    assert.equal(f.requests.length, 4);
    f.pagination.destroy();
});

test('new issues during a slow first-page request coalesce without aborting it', async () => {
    const f = fixture();
    const initial = f.pagination.selectGame('jnd28');
    f.pagination.observeLatest('jnd28', '100');
    f.pagination.observeLatest('jnd28', '101');
    f.pagination.observeLatest('jnd28', '102');
    assert.equal(f.requests.length, 1);
    assert.equal(f.requests[0].options.signal.aborted, false);
    f.response(0);
    await initial;
    assert.equal(f.requests.length, 2);
    assert.equal(f.params(1).snapshot, '0');
    f.response(1, {list: [{period: '102'}]});
    await settle();
    assert.equal(f.requests.length, 2);
    assert.equal(f.rendered.at(-1).list[0].period, '102');
    f.pagination.destroy();
});

test('a first-page response already containing the observed issue needs no duplicate refresh', async () => {
    const f = fixture();
    const initial = f.pagination.selectGame('az10');
    f.pagination.observeLatest('az10', '100');
    f.pagination.observeLatest('az10', '101');
    f.response(0, {list: [{issue: '101'}]});
    await initial;
    assert.equal(f.requests.length, 1);
    f.pagination.observeLatest('az10', '101');
    assert.equal(f.requests.length, 1);
    f.pagination.destroy();
});

test('first live observation arriving after an older snapshot queues one corrective read', async () => {
    const f = fixture();
    const initial = f.pagination.selectGame('az10');
    f.pagination.observeLatest('az10', '101');
    f.response(0, {list: [{issue: '100'}]});
    await initial;
    assert.equal(f.requests.length, 2);
    f.pagination.observeLatest('az10', '101');
    f.response(1, {list: [{issue: '101'}]});
    await settle();
    assert.equal(f.requests.length, 2);
    assert.equal(f.rendered.at(-1).list[0].issue, '101');
    f.pagination.destroy();
});

test('a new live issue queues while returning from a historical page to the latest page', async () => {
    const f = fixture();
    await firstPage(f);
    const historical = f.click('next');
    f.response(1);
    await historical;
    const latest = f.click('first');
    f.pagination.observeLatest('jnd28', '101');
    f.response(2, {list: [{period: '100'}]});
    await latest;
    assert.equal(f.requests.length, 4);
    assert.equal(f.params(3).snapshot, '0');
    f.response(3, {list: [{period: '101'}]});
    await settle();
    assert.equal(f.rendered.at(-1).list[0].period, '101');
    assert.equal(f.requests.length, 4);
    f.pagination.destroy();
});

test('timeout unlocks controls for retry and destruction removes listeners and stale completions', async () => {
    const f = fixture({abortable: true});
    const initial = f.pagination.selectGame('jnd28');
    const timeout = [...f.timeouts.values()][0];
    assert.equal(timeout.delay, 12000);
    timeout.callback();
    await initial;
    assert.equal(f.nodes['[data-page-action]'][0].disabled, false);
    assert.equal(f.nodes['[data-page-error]'][0].hidden, false);
    const retry = f.click('first');
    f.pagination.destroy();
    await retry;
    assert.equal(f.requests[1].options.signal.aborted, true);
    assert.equal(f.listeners.size, 0);
    assert.equal(f.timeouts.size, 0);
    assert.equal(f.rendered.length, 0);
    f.pagination.selectGame('az10');
    f.pagination.observeLatest('jnd28', '101');
    assert.equal(f.requests.length, 2);
});

test('wrong game or changing snapshot responses cannot replace the displayed history', async () => {
    const f = fixture();
    await firstPage(f);
    const badGame = f.click('next');
    f.response(1, {game: {code: 'az51'}});
    await badGame;
    const badSnapshot = f.click('next');
    f.response(2, {pagination: {page: 2, page_size: 20, total: 240, total_pages: 12, snapshot: 1001}});
    await badSnapshot;
    assert.equal(f.rendered.length, 1);
    assert.equal(f.pagination.pagination.page, 1);
    assert.equal(f.pagination.pagination.snapshot, 1000);
    f.pagination.destroy();
});

test('live data recovery retries an initially failed history request without duplicate requests', async () => {
    for (const issue of ['100', undefined]) {
        const f = fixture();
        const initial = f.pagination.selectGame('jnd28');
        f.requests[0].reject(new Error('center unavailable'));
        await initial;
        assert.equal(f.pagination.hasData, false);
        assert.notEqual(f.pagination.error, '');
        f.pagination.observeLatest('az10', issue);
        assert.equal(f.requests.length, 1);
        const recovered = f.pagination.observeLatest('jnd28', issue);
        f.pagination.observeLatest('jnd28', issue);
        assert.equal(f.requests.length, 2);
        assert.equal(f.params(1).snapshot, '0');
        f.response(1);
        await recovered;
        assert.equal(f.pagination.hasData, true);
        assert.equal(f.pagination.error, '');
        assert.equal(f.rendered.length, 1);
        assert.equal(f.requests.length, 2);
        f.pagination.destroy();
    }
});
