<?php

use common\models\lottery\DrawSchedule;
use PHPUnit\Framework\TestCase;

/** 验证倒计时只使用真实开奖间隔，漏采、时区和停采时不会伪造新一轮。 */
final class DrawScheduleTest extends TestCase
{
    /** 构造按新到旧排列的北京时间，不写数据库。 */
    private function times(int $latest, array $gaps): array
    {
        $times = [gmdate('Y-m-d H:i:s', $latest + 28800)];
        foreach ($gaps as $gap) {
            $latest -= $gap;
            $times[] = gmdate('Y-m-d H:i:s', $latest + 28800);
        }
        return $times;
    }

    /** 两类彩种分别保持其真实间隔，不共用加拿大28的210秒。 */
    public function testGameIntervalsAndServerClock(): void
    {
        $latest = 1792396800;
        foreach ([75, 300] as $interval) {
            $period = DrawSchedule::estimate($this->times($latest, array_fill(0, 8, $interval)), $latest + 15);
            self::assertSame($latest + 15, $period['server_time']);
            self::assertSame($interval, $period['interval_seconds']);
            self::assertSame($latest + $interval, $period['next_draw_at']);
        }
    }

    /** 漏采和休市不覆盖多数正常间隔。 */
    public function testMissingRoundsAndOvernightGap(): void
    {
        $period = DrawSchedule::estimate($this->times(1792396800, [75, 150, 75, 75, 18000, 75]), 1792396810);
        self::assertSame(75, $period['interval_seconds']);
    }

    /** 样本不足、未来开奖、非法日期和不稳定频率均不输出猜测时间。 */
    public function testUnknownSchedule(): void
    {
        foreach ([[], ['2026-10-19 15:00:00'], ['bad', 'bad', 'bad'],
            $this->times(1792396800, [75]), $this->times(1792396900, [75, 75]),
            $this->times(1792396800, [75, 300, 150, 200]),
            $this->times(1792396800, [75, 75, 300, 300])] as $times) {
            self::assertNull(DrawSchedule::estimate($times, 1792396810)['next_draw_at']);
        }
    }

    /** 旧数据始终保留原预计时间，前台据此等待开奖而非循环虚假倒计时。 */
    public function testStaleDrawDoesNotAdvanceDeadline(): void
    {
        $times = $this->times(1792396800, [300, 300, 300]);
        self::assertSame(1792397100, DrawSchedule::estimate($times, 1792483200)['next_draw_at']);
    }

    /** 主机默认时区不会改变接口内北京时间的含义。 */
    public function testTimezoneIndependence(): void
    {
        $previous = date_default_timezone_get();
        try {
            date_default_timezone_set('America/New_York');
            $period = DrawSchedule::estimate($this->times(1792396800, [75, 75]), 1792396800);
            self::assertSame(1792396875, $period['next_draw_at']);
        } finally { date_default_timezone_set($previous); }
    }
}
