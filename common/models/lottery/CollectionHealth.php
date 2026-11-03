<?php

namespace common\models\lottery;

/** 请求成功与开奖时效独立；延迟仅表示待更新，不据此断言上游故障。 */
final class CollectionHealth
{
    public static function describe(array $times, ?int $now = null, ?int $expectedInterval = null): array
    {
        $now ??= time();
        $latest = $times[0] ?? null;
        $stamp = $latest === null ? false : strtotime($latest . ' Asia/Shanghai');
        $age = $stamp === false ? null : max(0, $now - $stamp);
        $interval = $expectedInterval ?? DrawSchedule::estimate($times, $now)['interval_seconds'] ?? 210;
        return ['latest_draw_time' => $latest, 'age_seconds' => $age,
            'delayed' => $age === null || $stamp > $now || $age > max(180, $interval * 3)];
    }
}
