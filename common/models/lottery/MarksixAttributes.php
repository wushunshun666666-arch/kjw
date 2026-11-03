<?php

namespace common\models\lottery;

/** 只读展示分类，不参与采集、修改开奖、投注或结算。 */
final class MarksixAttributes
{
    public const VERSION = 'lunar-new-year-49-tie-v1';
    public const ZODIACS = ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'];
    public const WAVES = [
        '红波' => [1, 2, 7, 8, 12, 13, 18, 19, 23, 24, 29, 30, 34, 35, 40, 45, 46],
        '蓝波' => [3, 4, 9, 10, 14, 15, 20, 25, 26, 31, 36, 37, 41, 42, 47, 48],
        '绿波' => [5, 6, 11, 16, 17, 21, 22, 27, 28, 32, 33, 38, 39, 43, 44, 49],
    ];

    public static function lunarYear(string $drawTime): ?int
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $drawTime, new \DateTimeZone('Asia/Shanghai'));
        if ($date === false || $date->format('Y-m-d H:i:s') !== $drawTime || !class_exists(\IntlDateFormatter::class)) { return null; }
        // ICU的r是农历年份对应的公历年份；不是当前公历年或60年循环年号。
        static $formatter;
        $formatter ??= new \IntlDateFormatter('en_US@calendar=chinese', \IntlDateFormatter::NONE,
            \IntlDateFormatter::NONE, 'Asia/Shanghai', \IntlDateFormatter::TRADITIONAL, 'r');
        $year = $formatter->format($date);
        return is_string($year) && preg_match('/^[0-9]{4}$/D', $year) && (int) $year >= 1901 && (int) $year <= 2100
            ? (int) $year : null;
    }

    public static function wave(int $number): string
    {
        foreach (self::WAVES as $name => $numbers) { if (in_array($number, $numbers, true)) { return $name; } }
        throw new \InvalidArgumentException('六合彩号码必须在1至49之间。');
    }

    public static function calculate(array $balls, string $drawTime): array
    {
        if (count($balls) !== 7 || !array_is_list($balls) || count(array_unique($balls)) !== 7) {
            throw new \InvalidArgumentException('六合彩必须为七个不重复号码。');
        }
        $year = self::lunarYear($drawTime);
        $zodiacs = []; $waves = [];
        foreach ($balls as $number) {
            if (!is_int($number)) { throw new \InvalidArgumentException('六合彩号码必须为整数。'); }
            $waves[] = self::wave($number);
            $zodiacs[] = $year === null ? '-' : self::ZODIACS[(($year - 2020 - ($number - 1)) % 12 + 12) % 12];
        }
        $number = $balls[6];
        $size = $number === 49 ? '和' : ($number <= 24 ? '小' : '大');
        $parity = $number === 49 ? '和' : ($number % 2 ? '单' : '双');
        return ['rule_version' => self::VERSION, 'lunar_year' => $year, 'zodiacs' => $zodiacs, 'waves' => $waves,
            'special' => ['number' => $number, 'zodiac' => $zodiacs[6], 'wave' => $waves[6],
                'size' => $size, 'parity' => $parity, 'combo' => $number === 49 ? '和' : $size . $parity]];
    }

    public static function analysisOptions(): array
    {
        return [
            ['key' => 'special-zodiac', 'label' => '特码生肖', 'field' => 'marksix_special', 'attribute' => 'zodiac', 'values' => [...self::ZODIACS, '-']],
            ['key' => 'special-wave', 'label' => '特码波色', 'field' => 'marksix_special', 'attribute' => 'wave', 'values' => array_keys(self::WAVES)],
            ['key' => 'special-size', 'label' => '特码大小', 'field' => 'marksix_special', 'attribute' => 'size', 'values' => ['大', '小', '和']],
            ['key' => 'special-parity', 'label' => '特码单双', 'field' => 'marksix_special', 'attribute' => 'parity', 'values' => ['单', '双', '和']],
            ['key' => 'special-combo', 'label' => '特码组合', 'field' => 'marksix_special', 'attribute' => 'combo', 'values' => ['大单', '大双', '小单', '小双', '和']],
        ];
    }
}
