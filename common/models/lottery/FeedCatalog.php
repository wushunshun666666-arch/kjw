<?php

namespace common\models\lottery;

/** 用户指定的采集来源与号码规则白名单，不接受任意外部 URL。 */
final class FeedCatalog
{
    public const GAMES = [
        'jnd28' => ['name' => '加拿大28', 'type' => '28', 'count' => 3, 'min' => 0, 'max' => 9, 'query' => 'jnd28'],
        'az51' => ['name' => '澳洲幸运5', 'type' => 'ssc', 'count' => 5, 'min' => 0, 'max' => 9, 'query' => '10010'],
        'xyft_168' => ['name' => '幸运飞艇', 'type' => 'racing', 'count' => 10, 'min' => 1, 'max' => 10, 'query' => '10057'],
        'jspk10' => ['name' => '急速赛车', 'type' => 'racing', 'count' => 10, 'min' => 1, 'max' => 10, 'query' => '10037'],
        'jsssc' => ['name' => '急速时时彩', 'type' => 'ssc', 'count' => 5, 'min' => 0, 'max' => 9, 'query' => '10036'],
        'jsft_168' => ['name' => '急速飞艇', 'type' => 'racing', 'count' => 10, 'min' => 1, 'max' => 10, 'query' => '10035'],
        'az10' => ['name' => '澳洲幸运10', 'type' => 'racing', 'count' => 10, 'min' => 1, 'max' => 10, 'query' => '10012'],
        'hk6' => ['name' => '香港六合彩', 'type' => 'marksix', 'count' => 7, 'min' => 1, 'max' => 49, 'query' => '10048'],
        'macau_new' => ['name' => '新澳门六合彩', 'type' => 'marksix', 'count' => 7, 'min' => 1, 'max' => 49, 'query' => 'macau_new'],
        'macau_old' => ['name' => '老澳门六合彩', 'type' => 'marksix', 'count' => 7, 'min' => 1, 'max' => 49, 'query' => 'macau_old'],
    ];

    /** 返回指定来源配置；未知来源不可采集。 */
    public static function get(string $code): ?array { return self::GAMES[$code] ?? null; }

    /** 按彩种固定来源主机与路径，不接受请求传入任意地址。 */
    public static function url(string $code): string
    {
        $definition = self::get($code);
        if ($definition === null) { throw new \InvalidArgumentException('未配置该彩种的采集地址。'); }
        return 'http://69.165.75.20:86/lottery/api/history?lotCode=' . $definition['query'];
    }

    /** 确保后台规则与固定采集源一致，避免错误配置造成串库。 */
    public static function matches(Game $game): bool
    {
        $definition = self::get($game->code);
        return $definition !== null && (int) $game->ball_count === $definition['count']
            && (int) $game->ball_min === $definition['min'] && (int) $game->ball_max === $definition['max'];
    }

    /** 按彩种校验数量、范围以及赛车和六合彩号码互不重复。 */
    public static function accepts(string $code, array $balls): bool
    {
        $definition = self::get($code);
        return $definition !== null && count($balls) === $definition['count']
            && min($balls) >= $definition['min'] && max($balls) <= $definition['max']
            && (!in_array($definition['type'], ['racing', 'marksix'], true) || count(array_unique($balls)) === $definition['count']);
    }

    /** 元信息供展示和共享协议同时使用。 */
    public static function positions(string $code): array
    {
        return match (self::get($code)['type']) {
            '28' => ['第一位', '第二位', '第三位'],
            'marksix' => ['正码一', '正码二', '正码三', '正码四', '正码五', '正码六', '特码'],
            'racing' => array_map(static fn (int $n): string => '第' . $n . '名', range(1, 10)),
            default => ['万位', '千位', '百位', '十位', '个位'],
        };
    }

    public static function sumRange(string $code): array
    {
        return match (self::get($code)['type']) { '28' => [0, 27], 'marksix' => [28, 322], 'racing' => [3, 19], default => [0, 45] };
    }
}
