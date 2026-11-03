<?php

use common\models\lottery\MarksixAttributes;
use PHPUnit\Framework\TestCase;

final class MarksixAttributesTest extends TestCase
{
    public function testWavesCoverEveryNumberExactlyOnce(): void
    {
        self::assertSame([17, 16, 16], array_map('count', array_values(MarksixAttributes::WAVES)));
        $all = array_merge(...array_values(MarksixAttributes::WAVES)); sort($all);
        self::assertSame(range(1, 49), $all);
        foreach (MarksixAttributes::WAVES as $wave => $numbers) {
            foreach ($numbers as $number) { self::assertSame($wave, MarksixAttributes::wave($number)); }
        }
    }

    public function testZodiacChangesAtLunarNewYearRatherThanJanuaryOrSpringCommences(): void
    {
        foreach (['2026-01-01 00:00:00' => 2025, '2026-02-04 12:00:00' => 2025,
            '2026-02-16 23:59:59' => 2025, '2026-02-17 00:00:00' => 2026,
            '2025-01-28 23:59:59' => 2024, '2025-01-29 00:00:00' => 2025] as $time => $year) {
            self::assertSame($year, MarksixAttributes::lunarYear($time));
        }
        $before = MarksixAttributes::calculate([1, 2, 3, 4, 5, 6, 49], '2026-02-16 23:59:59');
        $after = MarksixAttributes::calculate([1, 2, 3, 4, 5, 6, 49], '2026-02-17 00:00:00');
        self::assertSame('蛇', $before['zodiacs'][0]);
        self::assertSame('马', $after['zodiacs'][0]);
        self::assertSame('蛇', $after['zodiacs'][1]);
        self::assertSame('马', $after['special']['zodiac']);
        // 不管何时读取，同一历史开奖都使用自己的日期。
        self::assertSame($before, MarksixAttributes::calculate([1, 2, 3, 4, 5, 6, 49], '2026-02-16 23:59:59'));
    }

    public function testSpecialClassificationBoundariesAnd49Tie(): void
    {
        foreach ([24 => ['小', '双', '小双'], 25 => ['大', '单', '大单'],
            48 => ['大', '双', '大双'], 49 => ['和', '和', '和']] as $number => $expected) {
            $result = MarksixAttributes::calculate([1, 2, 3, 4, 5, 6, $number], '2026-11-03 21:32:32')['special'];
            self::assertSame($number, $result['number']);
            self::assertSame($expected, [$result['size'], $result['parity'], $result['combo']]);
        }
    }

    public function testInvalidDateDoesNotInventZodiac(): void
    {
        $result = MarksixAttributes::calculate([1, 2, 3, 4, 5, 6, 49], '2026-02-30 00:00:00');
        self::assertNull($result['lunar_year']);
        self::assertSame(array_fill(0, 7, '-'), $result['zodiacs']);
        self::assertSame('绿波', $result['special']['wave']);
    }
}
