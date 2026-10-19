<?php

namespace common\models\lottery;

/** 确定性的近期加权频次算法；输出为历史统计推算，不是中奖概率。 */
final class PredictionAlgorithm
{
    public const VERSION = 'weighted-frequency-v1';
    public const WINDOW = 100;
    public const MIN_SAMPLES = 20;
    public const INTERVAL_SECONDS = 210;

    /** 根据从新到旧的已知历史计算大小、单双与反向组合。
     * @param array<int, array{issue:string,numbers:string,draw_time:string}> $history 已验证的数据库记录
     */
    public function calculate(array $history): ?array
    {
        $history = array_slice($history, 0, self::WINDOW);
        $count = count($history);
        if ($count < self::MIN_SAMPLES) { return null; }
        $scores = ['大' => 1, '小' => 1, '单' => 1, '双' => 1];
        $latest = [];
        foreach ($history as $index => $row) {
            $sum = array_sum(array_map('intval', explode(',', $row['numbers'])));
            $dx = $sum >= 14 ? '大' : '小';
            $ds = $sum % 2 === 0 ? '双' : '单';
            if ($index === 0) { $latest = [$dx, $ds]; }
            $scores[$dx] += $count - $index;
            $scores[$ds] += $count - $index;
        }
        $dx = $scores['大'] === $scores['小'] ? $latest[0] : ($scores['大'] > $scores['小'] ? '大' : '小');
        $ds = $scores['单'] === $scores['双'] ? $latest[1] : ($scores['单'] > $scores['双'] ? '单' : '双');
        return ['pdx' => $dx, 'pds' => $ds, 'sample_count' => $count,
            'basis_json' => json_encode($history, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'scores_json' => json_encode($scores, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)];
    }
}
