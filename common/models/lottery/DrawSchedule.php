<?php

namespace common\models\lottery;

/** 从已入库的开奖时间估算下一次开奖，缺少稳定样本时不编造时间。 */
final class DrawSchedule
{
    /**
     * 使用最近20个时间差的多数间隔，忽略漏采和跨日休市造成的长间隔。
     *
     * @param string[] $drawTimes 按新到旧排列的北京时间
     * @return array{server_time:int, next_draw_at:?int, interval_seconds:?int}
     */
    public static function estimate(array $drawTimes, ?int $now = null): array
    {
        $now ??= time();
        $period = ['server_time' => $now, 'next_draw_at' => null, 'interval_seconds' => null];
        $times = [];
        foreach (array_slice($drawTimes, 0, 21) as $value) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('Asia/Shanghai'));
            if ($date === false || $date->format('Y-m-d H:i:s') !== $value) { return $period; }
            $times[] = $date->getTimestamp();
        }
        if (count($times) < 3 || $times[0] > $now) { return $period; }
        $intervals = [];
        for ($index = 1; $index < count($times); $index++) {
            $seconds = $times[$index - 1] - $times[$index];
            if ($seconds > 0 && $seconds <= 1800) { $intervals[] = $seconds; }
        }
        if (count($intervals) < 2) { return $period; }
        $frequencies = array_count_values($intervals);
        arsort($frequencies);
        $seconds = (int) array_key_first($frequencies);
        if ($frequencies[$seconds] < 2 || $frequencies[$seconds] <= count($intervals) / 2) { return $period; }
        $period['interval_seconds'] = $seconds;
        $period['next_draw_at'] = $times[0] + $seconds;
        return $period;
    }
}
