import {readFile} from 'node:fs/promises';
import {test} from 'node:test';
import assert from 'node:assert/strict';
import vm from 'node:vm';

const source = await readFile(new URL('../../web/js/portal-website-configuration.js', import.meta.url), 'utf8');
const {WebsiteConfiguration} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const bundle = await readFile(new URL('../../web/assets1/website-BTB8_uk9.js', import.meta.url), 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));

function fixture(apply) {
    const requests = [];
    const applied = [];
    const timers = new Map();
    let timerId = 0;
    const configuration = new WebsiteConfiguration({
        request: () => new Promise((resolve, reject) => requests.push({resolve, reject})),
        apply: apply || (data => applied.push(data)),
        timers: {
            setTimeout(callback, delay) { timers.set(++timerId, {callback, delay}); return timerId; },
            clearTimeout(id) { timers.delete(id); },
        },
    });
    return {
        configuration, requests, applied, timers,
        retry() {
            assert.equal(timers.size, 1);
            const [id, task] = timers.entries().next().value;
            timers.delete(id);
            task.callback();
            return task.delay;
        },
    };
}

const response = ad => ({code: 200, data: {ad, show_qrcode: 0, landing: 'https://example.test/'}});

test('successful configuration is applied once and repeated mounts reuse it without another request', async () => {
    const f = fixture();
    assert.equal(f.requests.length, 0);
    const first = f.configuration.load();
    assert.strictEqual(f.configuration.load(), first);
    assert.equal(f.requests.length, 1);
    const data = response([{cover: '/assets1/ad.gif', link: 'https://example.test/'}]);
    f.requests[0].resolve(data);
    assert.equal(await first, true);
    assert.deepEqual(f.applied, [data.data]);
    assert.equal(f.timers.size, 0);
    assert.equal(await f.configuration.load(), true);
    assert.equal(f.requests.length, 1);
});

test('network rejection and unsuccessful API responses recover without overlapping requests', async () => {
    const f = fixture();
    const completion = f.configuration.load();
    f.requests[0].reject(new Error('connection interrupted'));
    await settle();
    assert.strictEqual(f.configuration.load(), completion);
    assert.equal(f.requests.length, 1);
    assert.equal(f.retry(), 2000);
    assert.strictEqual(f.configuration.load(), completion);
    assert.equal(f.requests.length, 2);
    f.requests[1].resolve({code: 503, msg: 'temporarily unavailable'});
    await settle();
    assert.equal(f.applied.length, 0);
    assert.equal(f.retry(), 4000);
    const data = response([{cover: '/uploads/advertisement.gif', link: '/contact'}]);
    f.requests[2].resolve(data);
    assert.equal(await completion, true);
    assert.deepEqual(f.applied, [data.data]);
    assert.equal(f.timers.size, 0);
    assert.equal(f.requests.length, 3);
});

test('malformed success data is retried, while an intentionally empty advertisement list is accepted', async () => {
    const f = fixture();
    const completion = f.configuration.load();
    for (const data of [null, [], {}, {ad: null}]) {
        f.requests.at(-1).resolve({code: 200, data});
        await settle();
        assert.equal(f.applied.length, 0);
        f.retry();
    }
    f.requests.at(-1).resolve(response([]));
    assert.equal(await completion, true);
    assert.deepEqual(f.applied[0].ad, []);
    assert.equal(f.applied[0].show_qrcode, 0);
    assert.equal(f.timers.size, 0);
});

test('persistent failure is bounded to six requests with capped backoff even after repeated initialization', async () => {
    const f = fixture();
    const completion = f.configuration.load();
    const delays = [];
    for (let attempt = 0; attempt < 6; attempt += 1) {
        assert.strictEqual(f.configuration.load(), completion);
        assert.equal(f.requests.length, attempt + 1);
        f.requests[attempt].resolve({code: 400});
        await settle();
        if (attempt < 5) delays.push(f.retry());
    }
    assert.equal(await completion, false);
    assert.deepEqual(delays, [2000, 4000, 8000, 16000, 30000]);
    assert.equal(f.timers.size, 0);
    assert.equal(f.applied.length, 0);
    assert.equal(await f.configuration.load(), false);
    assert.equal(f.requests.length, 6);
});

test('destroy cancels scheduled recovery and ignores a response already in flight', async () => {
    const waiting = fixture();
    const waitingResult = waiting.configuration.load();
    waiting.requests[0].reject(new Error('offline'));
    await settle();
    waiting.configuration.destroy();
    assert.equal(await waitingResult, false);
    assert.equal(waiting.timers.size, 0);

    const pending = fixture();
    const pendingResult = pending.configuration.load();
    pending.configuration.destroy();
    pending.requests[0].resolve(response([{cover: '/image.gif'}]));
    await settle();
    assert.equal(await pendingResult, false);
    assert.equal(pending.applied.length, 0);
    assert.equal(pending.timers.size, 0);
});

test('published store renders the draw panel immediately and configuration recovery never starts a draw timer', async () => {
    let cycles = 0;
    const store = {is_init: false, cycle() { cycles += 1; }};
    const applyBody = bundle.match(/apply:function\(t\)\{(.*?)\}\}\);export\{o as W\}/)?.[1];
    assert.ok(applyBody, 'the published store connects configuration to its reactive fields');
    const apply = vm.runInNewContext(`(function(t){${applyBody}})`, {o: store});
    const f = fixture(apply);
    const initBody = bundle.match(/init:(function\(\)\{.*?\}),open:function/)?.[1];
    assert.ok(initBody, 'the published store contains its initialization adapter');
    const init = vm.runInNewContext(`(${initBody})`, {o: store, portalConfiguration: f.configuration});
    const completion = init.call(store);
    assert.equal(store.is_init, true);
    assert.equal(cycles, 0);
    assert.strictEqual(init.call(store), completion);
    f.requests[0].reject(new Error('network interrupted'));
    await settle();
    f.retry();
    f.requests[1].resolve(response([null, {cover: ''}, {cover: '/ad.gif', link: '/login'}]));
    assert.equal(await completion, true);
    assert.deepEqual(store.ad, [{cover: '/ad.gif', link: '/login'}]);
    assert.equal(store.show_qrcode, 0);
    assert.equal(store.landing, 'https://example.test/');
    assert.equal(cycles, 0);
    assert.equal(f.requests.length, 2);
});
