<?php

namespace common\models\lottery;

/** 用户指定的采集来源与号码规则白名单，不接受任意外部 URL。 */
final class FeedCatalog
{
    public const GAMES = [
        'jnd28' => ['name' => '加拿大28', 'type' => '28', 'count' => 3, 'min' => 0, 'max' => 9, 'query' => 'jnd28'],
        'az51' => ['name' => '澳洲幸运5', 'type' => 'ssc', 'count' => 5, 'min' => 0, 'max' => 9, 'query' => 'az51&nums=1'],
        'xyft_168' => ['name' => '幸运飞艇', 'type' => 'racing', 'count' => 10, 'min' => 1, 'max' => 10, 'query' => 'xyft_168'],
        'jspk10' => ['name' => '急速赛车', 'type' => 'racing', 'count' => 10, 'min' => 1, 'max' => 10, 'query' => 'jspk10'],
        'jsssc' => ['name' => '急速时时彩', 'type' => 'ssc', 'count' => 5, 'min' => 0, 'max' => 9, 'query' => 'jsssc'],
        'jsft_168' => ['name' => '急速飞艇', 'type' => 'racing', 'count' => 10, 'min' => 1, 'max' => 10, 'query' => 'jsft_168'],
        'az10' => ['name' => '澳洲幸运10', 'type' => 'racing', 'count' => 10, 'min' => 1, 'max' => 10, 'query' => 'az10&nums=1'],
    ];

    /** 返回指定来源配置；未知来源不可采集。 */
    public static function get(string $code): ?array { return self::GAMES[$code] ?? null; }

    /** 固定主机与路径，保留用户提供的查询参数。 */
    public static function url(string $code): string
    {
        $definition = self::get($code);
        if ($definition === null) { throw new \InvalidArgumentException('未配置该彩种的采集地址。'); }
        return 'http://125.77.158.146:9501/Api/getGameData.php?gamename=' . $definition['query'];
    }

    /** 确保后台规则与固定采集源一致，避免错误配置造成串库。 */
    public static function matches(Game $game): bool
    {
        $definition = self::get($game->code);
        return $definition !== null && (int) $game->ball_count === $definition['count']
            && (int) $game->ball_min === $definition['min'] && (int) $game->ball_max === $definition['max'];
    }

    /** 按彩种校验数量、范围以及赛车号码互不重复。 */
    public static function accepts(string $code, array $balls): bool
    {
        $definition = self::get($code);
        return $definition !== null && count($balls) === $definition['count']
            && min($balls) >= $definition['min'] && max($balls) <= $definition['max']
            && ($definition['type'] !== 'racing' || count(array_unique($balls)) === $definition['count']);
    }
}
