import {readFile} from 'node:fs/promises';
import {test} from 'node:test';
import assert from 'node:assert/strict';

const source = await readFile(new URL('../../web/js/portal-game-analysis.js', import.meta.url), 'utf8');
const {analysisValue, analysisCounts} = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);
const rows = [
    {balls: [1, 10, 2, 3, 4, 5, 6, 7, 8, 9], sum: 11, size: '小', parity: '单'},
    {balls: [8, 4, 2, 3, 1, 5, 6, 7, 9, 10], sum: 12, size: '大', parity: '双'},
    {balls: [9, 3, 2, 4, 1, 5, 6, 7, 8, 10], sum: 12, size: '大', parity: '双'},
];

test('totals and categories read the same stored result fields as the result table', () => {
    assert.equal(analysisValue(rows[0], {field: 'sum'}), 11);
    assert.equal(analysisValue(rows[0], {field: 'size'}), '小');
    assert.equal(analysisValue(rows[0], {field: 'parity'}), '单');
    assert.equal(analysisValue(rows[0], {field: 'balls', position: 1}), 10);
});

test('冠亚和 frequency counts each draw once and does not count all ten numbers', () => {
    assert.deepEqual(analysisCounts(rows, {field: 'sum', values: [11, 12, 13]}), [
        {value: 11, count: 1, percent: '33.3'}, {value: 12, count: 2, percent: '66.7'}, {value: 13, count: 0, percent: '0.0'},
    ]);
});

test('大小 and 单双 percentages use the actual selected sample size', () => {
    assert.deepEqual(analysisCounts(rows.slice(0, 2), {field: 'size', values: ['大', '小']}), [
        {value: '大', count: 1, percent: '50.0'}, {value: '小', count: 1, percent: '50.0'},
    ]);
    assert.deepEqual(analysisCounts(rows, {field: 'parity', values: ['单', '双']}), [
        {value: '单', count: 1, percent: '33.3'}, {value: '双', count: 2, percent: '66.7'},
    ]);
});

test('five-number totals include both zero and forty-five, without changing position analysis', () => {
    const draws = [{balls: [0, 0, 0, 0, 0], sum: 0}, {balls: [9, 9, 9, 9, 9], sum: 45}];
    assert.equal(analysisCounts(draws, {field: 'sum', values: [0, 45]})[1].count, 1);
    assert.equal(analysisCounts(draws, {field: 'balls', position: 4, values: [0, 9]})[0].count, 1);
    assert.deepEqual(analysisCounts([], {field: 'size', values: ['大', '小']}), [
        {value: '大', count: 0, percent: '0.0'}, {value: '小', count: 0, percent: '0.0'},
    ]);
});
