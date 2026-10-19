import {readFile} from 'node:fs/promises';
import {test} from 'node:test';
import assert from 'node:assert/strict';

const source = await readFile(new URL('../../web/js/portal-shared-status.js', import.meta.url), 'utf8');
const {updateSharedDataStatus} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const fetchedAt = Date.parse('2026-10-19T00:03:04Z') / 1000;

function statusElement() {
    return {
        textContent: 'previous message', hidden: false,
        set innerHTML(value) { throw new Error(`HTML must not be used: ${value}`); },
    };
}

test('a cached response shows its real fetch time in Beijing and fresh data clears it', () => {
    const element = statusElement();
    updateSharedDataStatus(element, {shared_data: {stale: true, fetched_at: fetchedAt}});
    assert.equal(element.hidden, false);
    assert.equal(element.textContent, '共享开奖数据连接暂时中断，当前显示缓存记录（更新于 2026-10-19 08:03:04 北京时间）');
    updateSharedDataStatus(element, {shared_data: {stale: false, fetched_at: fetchedAt}});
    assert.equal(element.hidden, true);
    assert.equal(element.textContent, '');
});

test('absent or malformed metadata never invents a connection failure', () => {
    for (const data of [undefined, null, {}, {shared_data: null}, {shared_data: []}, {shared_data: 'stale'},
        {shared_data: {stale: 'true'}}, {shared_data: {stale: 1}}, {shared_data: {fetched_at: fetchedAt}}]) {
        const element = statusElement();
        updateSharedDataStatus(element, data);
        assert.equal(element.hidden, true);
        assert.equal(element.textContent, '');
    }
});

test('invalid dates keep the stale notice but never show fabricated time or injected markup', () => {
    for (const fetched_at of [undefined, null, '', String(fetchedAt), -1, 1.5, NaN, Infinity,
        Number.MAX_SAFE_INTEGER, {}, [], '<img src=x onerror=alert(1)>']) {
        const element = statusElement();
        updateSharedDataStatus(element, {shared_data: {stale: true, fetched_at}});
        assert.equal(element.hidden, false);
        assert.equal(element.textContent, '共享开奖数据连接暂时中断，当前显示缓存记录');
    }
});

test('midnight remains 00 hours and the notice does not depend on the visitor timezone', () => {
    const element = statusElement();
    updateSharedDataStatus(element, {shared_data: {stale: true, fetched_at: Date.parse('2026-10-18T16:00:00Z') / 1000}});
    assert.equal(element.textContent, '共享开奖数据连接暂时中断，当前显示缓存记录（更新于 2026-10-19 00:00:00 北京时间）');
});

test('status rendering leaves draw data untouched and tolerates pages without a status element', () => {
    const shared = Object.freeze({stale: true, fetched_at: fetchedAt});
    const list = Object.freeze([Object.freeze({issue: '123', numbers: '1,2,3'})]);
    const data = Object.freeze({shared_data: shared, list});
    const element = statusElement();
    assert.doesNotThrow(() => updateSharedDataStatus(null, data));
    assert.doesNotThrow(() => updateSharedDataStatus(undefined, data));
    updateSharedDataStatus(element, data);
    assert.equal(data.list, list);
    assert.equal(data.shared_data, shared);
    assert.equal(data.list[0].issue, '123');
});
