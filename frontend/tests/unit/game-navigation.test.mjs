import {readFile} from 'node:fs/promises';
import {test} from 'node:test';
import assert from 'node:assert/strict';

const source = await readFile(new URL('../../web/js/portal-game-navigation.js', import.meta.url), 'utf8');
const {GameNavigation} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const snapshot = code => ({game: {code}, list: [{issue: '123'}]});
const settle = () => new Promise(resolve => setImmediate(resolve));

class Events {
    listeners = new Map();
    addEventListener(type, listener) {
        if (!this.listeners.has(type)) this.listeners.set(type, new Set());
        this.listeners.get(type).add(listener);
    }
    removeEventListener(type, listener) { this.listeners.get(type)?.delete(listener); }
    emit(type, event = {}) { this.listeners.get(type)?.forEach(listener => listener(event)); }
}

function fixture({initialGame = 'jnd28', initialData, abortable = false} = {}) {
    const root = new Events();
    const links = ['jnd28', 'az51', 'az10', 'xyft_168'].map(code => {
        const attributes = new Map();
        const classes = new Set();
        return {
            dataset: {game: code}, textContent: code, target: '',
            classList: {toggle(name, enabled) { if (enabled) classes.add(name); else classes.delete(name); }, contains: name => classes.has(name)},
            setAttribute: (name, value) => attributes.set(name, value),
            removeAttribute: name => attributes.delete(name),
            getAttribute: name => attributes.get(name),
            hasAttribute: name => attributes.has(name),
        };
    });
    root.querySelectorAll = () => links;
    root.contains = link => links.includes(link);
    const window = new Events();
    let href = `https://example.test/index.php?r=site%2Findex&game=${initialGame}#/?tab=trend`;
    Object.defineProperty(window, 'location', {get: () => new URL(href)});
    const historyCalls = [];
    const vueState = {back: null, current: '/', forward: null, position: 4, scroll: {top: 100}};
    window.history = {
        state: vueState,
        pushState(state, title, url) { this.state = state; href = url; historyCalls.push({method: 'push', state, url}); },
        replaceState(state, title, url) { this.state = state; href = url; historyCalls.push({method: 'replace', state, url}); },
    };
    const document = new Events();
    document.hidden = false;
    const timeouts = new Map();
    const intervals = new Map();
    let nextTimer = 0;
    const timers = {
        setTimeout(callback, delay) { const id = ++nextTimer; timeouts.set(id, {callback, delay}); return id; },
        clearTimeout(id) { timeouts.delete(id); },
        setInterval(callback, delay) { const id = ++nextTimer; intervals.set(id, {callback, delay}); return id; },
        clearInterval(id) { intervals.delete(id); },
    };
    const requests = [];
    const rendered = [];
    const selected = [];
    const errors = [];
    const busy = [];
    const canada = [];
    const navigation = new GameNavigation({
        root, initialGame, initialData, endpoint: '/index.php?r=reference%2Fgame', title: '开奖网',
        render: data => rendered.push(data), onGame: game => selected.push(game),
        setBusy: value => busy.push(value), onError: message => errors.push(message),
        activateCanada: () => canada.push('activate'), deactivateCanada: () => canada.push('deactivate'),
        environment: {window, document, timers, fetch: (url, options) => new Promise((resolve, reject) => {
            requests.push({url, options, resolve, reject});
            if (abortable) options.signal.addEventListener('abort', () => reject(new Error('aborted')), {once: true});
        })},
    });
    return {
        navigation, root, links, window, document, historyCalls, vueState, timers, timeouts, intervals,
        requests, rendered, selected, errors, busy, canada,
        complete(index, game) { requests[index].resolve({ok: true, json: async () => ({code: 200, data: snapshot(game)})}); },
        pop(game, hash = '#/?tab=trend') {
            href = `https://example.test/index.php?r=site%2Findex&game=${game}${hash}`;
            window.emit('popstate');
        },
        click(game, extra = {}) {
            const link = links.find(item => item.dataset.game === game);
            const event = {target: {closest: () => link}, button: 0, defaultPrevented: false,
                preventDefault() { this.defaultPrevented = true; }, ...extra};
            root.emit('click', event);
            return event;
        },
        poll() { intervals.forEach(timer => timer.callback()); },
    };
}

test('server-provided initial game renders without fetching or rewriting browser history', () => {
    const f = fixture({initialGame: 'az51', initialData: snapshot('az51')});
    assert.equal(f.rendered.length, 1);
    assert.equal(f.requests.length, 0);
    assert.deepEqual(f.selected, ['az51']);
    assert.deepEqual(f.canada, ['deactivate']);
    assert.equal(f.links[1].getAttribute('aria-current'), 'page');
    assert.equal(f.historyCalls.length, 0);
    f.navigation.destroy();
});

test('modified clicks remain native and selecting the active game starts no request', () => {
    const f = fixture();
    for (const modifier of ['ctrlKey', 'metaKey', 'shiftKey', 'altKey']) {
        assert.equal(f.click('az51', {[modifier]: true}).defaultPrevented, false);
    }
    assert.equal(f.click('az51', {button: 1}).defaultPrevented, false);
    assert.equal(f.click('jnd28').defaultPrevented, true);
    assert.equal(f.requests.length, 0);
    f.navigation.destroy();
});

test('successful switch preserves the hash and Vue history state, then resumes Canada without a data request', async () => {
    const f = fixture();
    const switchGame = f.navigation.select('az51');
    assert.equal(f.selected.at(-1), 'jnd28');
    assert.equal(f.historyCalls.length, 0);
    assert.equal(new URL(f.requests[0].url).searchParams.get('game'), 'az51');
    f.complete(0, 'az51');
    await switchGame;
    assert.equal(f.selected.at(-1), 'az51');
    assert.equal(f.window.location.hash, '#/?tab=trend');
    assert.equal(f.historyCalls[0].state, f.vueState);
    assert.equal(f.historyCalls[0].method, 'push');
    await f.navigation.select('jnd28');
    assert.equal(f.requests.length, 1);
    assert.equal(f.selected.at(-1), 'jnd28');
    assert.deepEqual(f.canada, ['activate', 'deactivate', 'activate']);
    assert.equal(f.links[1].classList.contains('selected'), false);
    f.navigation.destroy();
});

test('rapid switching cancels older requests and late responses cannot overwrite the selected game', async () => {
    const f = fixture();
    const first = f.navigation.select('az51');
    const last = f.navigation.select('az10');
    assert.equal(f.requests[0].options.signal.aborted, true);
    f.complete(1, 'az10');
    await last;
    f.complete(0, 'az51');
    await first;
    assert.deepEqual(f.rendered.map(data => data.game.code), ['az10']);
    assert.equal(f.window.location.search.includes('game=az10'), true);
    assert.equal(f.historyCalls.length, 1);
    f.navigation.destroy();
});

test('a failed switch preserves current content and URL, and clicking it again retries', async () => {
    const f = fixture({initialGame: 'az51', initialData: snapshot('az51')});
    const previousUrl = f.window.location.href;
    const first = f.navigation.select('az10');
    f.requests[0].reject(new Error('offline'));
    await first;
    assert.equal(f.navigation.currentGame, 'az51');
    assert.equal(f.window.location.href, previousUrl);
    assert.match(f.errors.at(-1), /切换失败/);
    assert.deepEqual(f.busy, [true, false]);
    const retry = f.navigation.select('az10');
    f.complete(1, 'az10');
    await retry;
    assert.equal(f.navigation.currentGame, 'az10');
    f.navigation.destroy();
});

test('popstate changes games without pushing history and restores a failed destination', async () => {
    const f = fixture({initialGame: 'az51', initialData: snapshot('az51')});
    const previousUrl = f.window.location.href;
    f.pop('az10');
    f.requests[0].reject(new Error('offline'));
    await settle();
    assert.equal(f.window.location.href, previousUrl);
    assert.equal(f.historyCalls.at(-1).method, 'replace');
    assert.equal(f.navigation.currentGame, 'az51');
    f.pop('az10');
    f.complete(1, 'az10');
    await settle();
    assert.equal(f.navigation.currentGame, 'az10');
    assert.equal(f.historyCalls.some(call => call.method === 'push'), false);
    f.navigation.destroy();
});

test('clicking the current game cancels a pending popstate destination and restores its URL', async () => {
    const f = fixture({initialGame: 'az51', initialData: snapshot('az51')});
    const previousUrl = f.window.location.href;
    f.pop('az10');
    f.click('az51');
    assert.equal(f.requests[0].options.signal.aborted, true);
    assert.equal(f.window.location.href, previousUrl);
    f.complete(0, 'az10');
    await settle();
    assert.equal(f.navigation.currentGame, 'az51');
    f.navigation.destroy();
});

test('polling skips Canada and hidden documents and prevents overlap while preserving selection', async () => {
    const f = fixture({initialGame: 'az51', initialData: snapshot('az51')});
    f.document.hidden = true;
    f.poll();
    assert.equal(f.requests.length, 0);
    f.document.hidden = false;
    f.document.emit('visibilitychange');
    f.poll();
    assert.equal(f.requests.length, 1);
    f.complete(0, 'az51');
    await settle();
    assert.equal(f.rendered.length, 2);
    assert.deepEqual(f.selected, ['az51']);
    assert.equal(f.historyCalls.length, 0);
    await f.navigation.select('jnd28');
    f.poll();
    assert.equal(f.requests.length, 1);
    f.navigation.destroy();
});

test('requests time out at twelve seconds, release busy state and remain retryable', async () => {
    const f = fixture({abortable: true});
    const request = f.navigation.select('az51');
    const timer = [...f.timeouts.values()][0];
    assert.equal(timer.delay, 12000);
    timer.callback();
    await request;
    assert.equal(f.requests[0].options.signal.aborted, true);
    assert.equal(f.navigation.currentGame, 'jnd28');
    assert.equal(f.busy.at(-1), false);
    assert.equal(f.timeouts.size, 0);
    f.navigation.destroy();
});

test('mismatched payloads cannot relabel stale data as a different game', async () => {
    const f = fixture();
    const request = f.navigation.select('az10');
    f.complete(0, 'az51');
    await request;
    assert.equal(f.rendered.length, 0);
    assert.equal(f.navigation.currentGame, 'jnd28');
    assert.equal(f.historyCalls.length, 0);
    f.navigation.destroy();
});

test('leaving the homepage ignores other-route history and destruction cancels all future updates', async () => {
    const f = fixture();
    f.pop('az10', '#/notice');
    assert.equal(f.requests.length, 0);
    const request = f.navigation.select('az51');
    f.navigation.destroy();
    assert.equal(f.requests[0].options.signal.aborted, true);
    assert.equal(f.intervals.size, 0);
    assert.equal(f.timeouts.size, 0);
    assert.equal(f.root.listeners.get('click').size, 0);
    assert.equal(f.window.listeners.get('popstate').size, 0);
    f.complete(0, 'az51');
    await request;
    assert.equal(f.rendered.length, 0);
});
