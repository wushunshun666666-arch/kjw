import {readFile} from 'node:fs/promises';
import {test} from 'node:test';
import assert from 'node:assert/strict';

const source = await readFile(new URL('../../web/js/portal-draw-interactions.js', import.meta.url), 'utf8');
const {countdownState} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

test('countdown follows server time and elapsed time, not the visitor clock', () => {
    const period = {server_time: 1000, next_draw_at: 1075};
    assert.deepEqual(countdownState(period, 0), {kind: 'counting', minutes: '1', seconds: '15', text: '1 分 15 秒'});
    assert.equal(countdownState(period, 16000).text, '0 分 59 秒');
    assert.equal(countdownState(period, 74999).text, '0 分 01 秒');
});

test('expiry and suspended tabs wait for a new stored draw without negative or rolling timers', () => {
    const period = {server_time: 1000, next_draw_at: 1075};
    assert.equal(countdownState(period, 75000).kind, 'waiting');
    assert.equal(countdownState(period, 3600000).kind, 'waiting');
    assert.equal(countdownState({server_time: 1200, next_draw_at: 1075}).kind, 'waiting');
});

test('no records or missing timing metadata show an explicit waiting state', () => {
    for (const period of [null, {}, {server_time: 1000, next_draw_at: null}, {server_time: NaN, next_draw_at: 1075}]) {
        assert.deepEqual(countdownState(period), {kind: 'unknown', text: '等待开奖时间'});
    }
});

test('the new draw resets to its own deadline and supports five-minute games', () => {
    assert.equal(countdownState({server_time: 1080, next_draw_at: 1150}).text, '1 分 10 秒');
    assert.equal(countdownState({server_time: 2000, next_draw_at: 2300}).text, '5 分 00 秒');
});
