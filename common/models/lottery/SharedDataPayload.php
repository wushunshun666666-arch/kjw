<?php

namespace common\models\lottery;

/** 共享协议的唯一白名单边界，校验中心响应并剔除站点配置及未知字段。 */
final class SharedDataPayload
{
    /** 将查询约束规范化，确保缓存键与实际中心请求一一对应。 */
    public static function parameters(string $resource, array $params): array
    {
        $allowed = $resource === 'latest' ? ['game'] : ['game', 'page', 'page_size', 'snapshot'];
        if (!in_array($resource, ['latest', 'history'], true) || array_diff(array_keys($params), $allowed) !== []
            || !is_string($params['game'] ?? null) || FeedCatalog::get($params['game']) === null) {
            throw new \InvalidArgumentException('共享开奖查询参数无效。');
        }
        $result = ['game' => $params['game']];
        if ($resource === 'history') {
            foreach (['page' => [1, 10000], 'page_size' => [20, 100], 'snapshot' => [0, 9007199254740991]] as $key => $range) {
                $value = $params[$key] ?? null;
                if (is_string($value) && preg_match('/^(0|[1-9][0-9]{0,15})$/D', $value)) {
                    $value = (int) $value;
                }
                if (!is_int($value) || $value < $range[0] || $value > $range[1]) {
                    throw new \InvalidArgumentException('共享开奖分页参数无效。');
                }
                $result[$key] = $value;
            }
            if (!in_array($result['page_size'], [20, 50, 100], true)) {
                throw new \InvalidArgumentException('共享开奖每页期数无效。');
            }
        }
        return $result;
    }

    /**
     * 验证版本、彩种、号码、时间、分类、预测和快照，返回可直接交给现有前台的数据。
     *
     * @throws \UnexpectedValueException 中心响应不符合已约定的公开协议
     */
    public static function validate(array $envelope, string $resource, array $params): array
    {
        $params = self::parameters($resource, $params);
        self::check(($envelope['version'] ?? null) === 1 && ($envelope['code'] ?? null) === 200);
        $data = $envelope['data'] ?? null;
        self::check(is_array($data) && is_array($data['game'] ?? null) && ($data['game']['code'] ?? null) === $params['game']);
        self::check(is_array($data['list'] ?? null) && array_is_list($data['list'])
            && count($data['list']) <= ($resource === 'history' ? $params['page_size'] : 100));
        $rows = [];
        $issues = [];
        $lastTime = null;
        foreach ($data['list'] as $row) {
            self::check(is_array($row));
            $clean = self::row($row, $params['game']);
            $issue = $clean[$params['game'] === 'jnd28' ? 'period' : 'issue'];
            $drawTime = $clean[$params['game'] === 'jnd28' ? 'created' : 'time'];
            self::check(!isset($issues[$issue]) && ($lastTime === null || $drawTime <= $lastTime));
            $issues[$issue] = true;
            $lastTime = $drawTime;
            $rows[] = $clean;
        }
        $result = ['game' => ['code' => $params['game']], 'list' => $rows];
        if ($resource === 'history') {
            $result['pagination'] = self::pagination($data['pagination'] ?? null, $params, count($rows));
        } elseif ($params['game'] === 'jnd28') {
            $result += self::canadaLatest($data, $rows);
        } else {
            $result['game'] = self::game($data['game'], $params['game']);
            $result['period'] = self::schedule($data['period'] ?? null);
            $collection = $data['collection'] ?? null;
            self::check(is_array($collection) && is_bool($collection['unavailable'] ?? null));
            foreach (['attempt_time', 'success_time'] as $key) {
                self::check(array_key_exists($key, $collection));
                if ($collection[$key] !== null) { self::date($collection[$key]); }
            }
            $result['collection'] = array_intersect_key($collection, array_flip(['attempt_time', 'success_time', 'unavailable']));
        }
        return $result;
    }

    /** 验证号码与衍生分类一致，杜绝把总和与冠亚和混用。 */
    private static function row(array $row, string $code): array
    {
        $canada = $code === 'jnd28';
        $issueKey = $canada ? 'period' : 'issue';
        $timeKey = $canada ? 'created' : 'time';
        $ballsKey = $canada ? 'code_arr' : 'balls';
        $sizeKey = $canada ? 'dx' : 'size';
        $parityKey = $canada ? 'ds' : 'parity';
        self::issue($row[$issueKey] ?? null);
        self::date($row[$timeKey] ?? null);
        $balls = $row[$ballsKey] ?? null;
        self::check(is_array($balls) && array_is_list($balls) && $balls !== []);
        foreach ($balls as $ball) { self::check(is_int($ball)); }
        self::check(FeedCatalog::accepts($code, $balls));
        $racing = FeedCatalog::get($code)['type'] === 'racing';
        $sum = $racing ? $balls[0] + $balls[1] : array_sum($balls);
        $boundary = $racing ? 12 : ($canada ? 14 : 23);
        self::check(($row['sum'] ?? null) === $sum && ($row[$sizeKey] ?? null) === ($sum >= $boundary ? '大' : '小')
            && ($row[$parityKey] ?? null) === ($sum % 2 === 0 ? '双' : '单'));
        $clean = array_intersect_key($row, array_flip([$issueKey, $timeKey, $ballsKey, 'sum', $sizeKey, $parityKey]));
        if ($canada) {
            self::check(is_bool($row['prediction_available'] ?? null));
            $clean['prediction_available'] = $row['prediction_available'];
            $clean += self::prediction($row, $clean['prediction_available']);
            foreach (['prdx' => 'pdx', 'prds' => 'pds'] as $hit => $value) {
                $actual = $value === 'pdx' ? $row['dx'] : $row['ds'];
                $expected = $clean['prediction_available'] ? (int) ($clean[$value] === $actual) : null;
                self::check(array_key_exists($hit, $row) && $row[$hit] === $expected);
                $clean[$hit] = $expected;
            }
            $result = implode(' + ', $balls) . ' = ' . $sum;
            self::check(($row['result'] ?? null) === $result);
            $clean['result'] = $result;
        }
        return $clean;
    }

    /** 保留事前预测的合法显示值，未知预测不会伪造命中或占位内容。 */
    private static function prediction(array $values, bool $available): array
    {
        $result = [];
        foreach (['pdx' => ['大', '小'], 'pds' => ['单', '双'], 'pbdx' => ['大', '小'],
            'pbds' => ['单', '双'], 'pbdxds' => ['大单', '大双', '小单', '小双']] as $key => $allowed) {
            self::check(in_array($values[$key] ?? null, $available ? $allowed : ['-'], true));
            $result[$key] = $values[$key];
        }
        if ($available) {
            self::check($result['pbdx'] !== $result['pdx'] && $result['pbds'] !== $result['pds']
                && $result['pbdxds'] === $result['pbdx'] . $result['pbds']);
        }
        return $result;
    }

    /** 加拿大28的当前期、下一期和本地预测与同批开奖保持一致。 */
    private static function canadaLatest(array $data, array $rows): array
    {
        self::check(($data['prediction_source'] ?? null) === 'local'
            && is_string($data['prediction_algorithm'] ?? null)
            && preg_match('/^[a-zA-Z0-9._-]{1,64}$/D', $data['prediction_algorithm']) === 1
            && is_bool($data['prediction_available'] ?? null) && is_array($data['prediction'] ?? null));
        $period = $data['period'] ?? null;
        self::check(is_array($period));
        self::issue($period['cperiod'] ?? null, true);
        self::issue($period['period'] ?? null, true);
        self::date($period['time'] ?? null);
        self::date($period['next_time_end'] ?? null);
        self::integer($period['open_time'] ?? null, 0, 86400);
        self::check($period['cperiod'] === ($rows[0]['period'] ?? '-')
            && ($data['prediction_period'] ?? null) === $period['period']);
        return [
            'period' => array_intersect_key($period, array_flip(['cperiod', 'period', 'open_time', 'time', 'next_time_end'])),
            'prediction_source' => 'local', 'prediction_algorithm' => $data['prediction_algorithm'],
            'prediction_available' => $data['prediction_available'], 'prediction_period' => $data['prediction_period'],
            'prediction' => self::prediction($data['prediction'], $data['prediction_available']),
        ];
    }

    /** 只接受与请求页和数据库快照对应的完整历史分页。 */
    private static function pagination(mixed $value, array $params, int $count): array
    {
        self::check(is_array($value));
        foreach (['page', 'page_size', 'total', 'total_pages', 'snapshot'] as $field) {
            self::integer($value[$field] ?? null, in_array($field, ['total', 'snapshot'], true) ? 0 : 1, 9007199254740991);
        }
        $pages = max(1, (int) ceil($value['total'] / $params['page_size']));
        self::check($value['page_size'] === $params['page_size'] && $value['total_pages'] === $pages
            && $value['page'] === min($params['page'], $pages)
            && ($params['snapshot'] === 0 || $value['snapshot'] === $params['snapshot'])
            && ($value['total'] === 0 || $value['snapshot'] > 0)
            && $count === min($params['page_size'], max(0, $value['total'] - ($value['page'] - 1) * $params['page_size'])));
        return array_intersect_key($value, array_flip(['page', 'page_size', 'total', 'total_pages', 'snapshot']));
    }

    /** 赛车/时时彩元信息只接受固定号码规则，客户站仍可覆盖本地展示名称。 */
    private static function game(array $game, string $code): array
    {
        $definition = FeedCatalog::get($code);
        $racing = $definition['type'] === 'racing';
        $positions = $racing ? array_map(static fn (int $rank): string => '第' . $rank . '名', range(1, 10))
            : ['万位', '千位', '百位', '十位', '个位'];
        $range = range($definition['min'], $definition['max']);
        self::text($game['name'] ?? null, 128);
        self::text($game['rule'] ?? null, 500);
        self::check(($game['type'] ?? null) === $definition['type'] && ($game['positions'] ?? null) === $positions
            && ($game['range'] ?? null) === $range && ($game['sum_label'] ?? null) === ($racing ? '冠亚和' : '总和'));
        $analysis = [
            ['key' => 'sum', 'label' => $racing ? '冠亚和' : '总和', 'field' => 'sum', 'values' => range($racing ? 3 : 0, $racing ? 19 : 45)],
            ['key' => 'size', 'label' => '大小', 'field' => 'size', 'values' => ['大', '小']],
            ['key' => 'parity', 'label' => '单双', 'field' => 'parity', 'values' => ['单', '双']],
        ];
        foreach ($positions as $index => $label) {
            $analysis[] = ['key' => 'position-' . $index, 'label' => $label, 'field' => 'balls', 'position' => $index, 'values' => $range];
        }
        self::check(is_array($game['analysis'] ?? null) && array_is_list($game['analysis']) && count($game['analysis']) === count($analysis));
        foreach ($analysis as $index => $expected) {
            self::check(is_array($game['analysis'][$index]));
            foreach ($expected as $key => $value) {
                self::check(($game['analysis'][$index][$key] ?? null) === $value);
            }
        }
        return ['code' => $code, 'name' => $game['name'], 'type' => $definition['type'], 'positions' => $positions,
            'range' => $range, 'sum_label' => $game['sum_label'], 'analysis' => $analysis, 'rule' => $game['rule']];
    }

    /** 时间戳与间隔必须成对出现，避免远端坏数据冻结倒计时。 */
    private static function schedule(mixed $period): array
    {
        self::check(is_array($period) && array_key_exists('next_draw_at', $period) && array_key_exists('interval_seconds', $period));
        self::integer($period['server_time'] ?? null, 1, 4102444800);
        self::check(($period['next_draw_at'] === null) === ($period['interval_seconds'] === null));
        if ($period['next_draw_at'] !== null) {
            self::integer($period['next_draw_at'], 1, 4102444800);
            self::integer($period['interval_seconds'], 1, 1800);
        }
        return array_intersect_key($period, array_flip(['server_time', 'next_draw_at', 'interval_seconds']));
    }

    /** 与数据库期号约束一致，下一期未确定时允许占位符。 */
    private static function issue(mixed $value, bool $placeholder = false): void
    {
        self::check(is_string($value) && (($placeholder && $value === '-') || preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $value) === 1));
    }

    /** 严格验证北京时间，不接受 PHP 自动修复的错误日期。 */
    private static function date(mixed $value): void
    {
        self::check(is_string($value) && strlen($value) === 19);
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value, new \DateTimeZone('Asia/Shanghai'));
        self::check($date !== false && $date->format('Y-m-d H:i:s') === $value);
    }

    /** 保持 JSON 整数语义，不把布尔、浮点或任意字符串当作数值。 */
    private static function integer(mixed $value, int $min, int $max): void
    {
        self::check(is_int($value) && $value >= $min && $value <= $max);
    }

    /** 展示文本限制长度并拒绝控制字符。 */
    private static function text(mixed $value, int $max): void
    {
        self::check(is_string($value) && $value !== '' && mb_strlen($value, 'UTF-8') <= $max && !preg_match('/[\x00-\x1f\x7f]/', $value));
    }

    /** 对外仅提供统一协议错误，原始中心报文不会进入日志。 */
    private static function check(bool $valid): void
    {
        if (!$valid) { throw new \UnexpectedValueException('共享开奖中心响应格式无效。'); }
    }
}
