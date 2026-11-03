<?php

use common\models\lottery\CollectionHealth;
use PHPUnit\Framework\TestCase;

final class CollectionHealthTest extends TestCase
{
    public function testAgeIsIndependentOfSuccessfulRequestsAndRespectsObservedInterval(): void
    {
        $now = strtotime('2026-11-02 12:00:00 Asia/Shanghai');
        self::assertFalse(CollectionHealth::describe(['2026-11-02 11:59:00'], $now)['delayed']);
        $old = CollectionHealth::describe(['2026-11-02 11:00:00'], $now);
        self::assertTrue($old['delayed']);
        self::assertSame(3600, $old['age_seconds']);
        self::assertSame('2026-11-02 11:00:00', $old['latest_draw_time']);
        self::assertTrue(CollectionHealth::describe([], $now)['delayed']);
        self::assertTrue(CollectionHealth::describe(['2026-11-02 13:00:00'], $now)['delayed']);
        self::assertFalse(CollectionHealth::describe(['2026-11-02 11:50:00', '2026-11-02 11:45:00', '2026-11-02 11:40:00'], $now)['delayed']);
    }
}
