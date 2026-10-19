<?php

use common\models\lottery\FeedCatalog;
use common\models\lottery\SharedDataConfig;
use common\models\lottery\SharedDataGateway;
use common\models\lottery\SharedDataPayload;
use PHPUnit\Framework\TestCase;
use yii\caching\ArrayCache;
use yii\web\HttpException;

/** 共享网关使用内存缓存和可控传输验收，不连接上游、不写业务数据库。 */
final class SharedDataGatewayTest extends TestCase
{
    /** 构造仅供测试的客户凭据，避免使用部署私有配置。 */
    private function config(array $changes = []): SharedDataConfig
    {
        return new SharedDataConfig(array_replace(['mode' => 'client', 'endpoint' => 'https://center.example/index.php',
            'client_id' => 'unit-client', 'token' => str_repeat('T', 64)], $changes));
    }

    /** 为七个彩种构造与现有公开服务一致的最新开奖。 */
    private function latest(string $code = 'jnd28'): array
    {
        $definition = FeedCatalog::get($code);
        $canada = $code === 'jnd28';
        $racing = $definition['type'] === 'racing';
        $balls = $racing ? range(1, 10) : ($canada ? [1, 2, 3] : [1, 2, 3, 4, 5]);
        $sum = $racing ? 3 : array_sum($balls);
        $prediction = ['pdx' => '大', 'pds' => '单', 'pbdx' => '小', 'pbds' => '双', 'pbdxds' => '小双'];
        $row = [$canada ? 'period' : 'issue' => '123456', $canada ? 'created' : 'time' => '2026-10-19 12:00:00',
            $canada ? 'code_arr' : 'balls' => $balls, 'sum' => $sum,
            $canada ? 'dx' : 'size' => '小', $canada ? 'ds' : 'parity' => $sum % 2 ? '单' : '双'];
        if ($canada) {
            $row += $prediction + ['prediction_available' => true, 'prdx' => 0, 'prds' => 0, 'result' => '1 + 2 + 3 = 6'];
            $data = ['game' => ['code' => $code], 'list' => [$row], 'prediction_source' => 'local',
                'prediction_algorithm' => 'weighted-frequency-v1', 'prediction' => $prediction,
                'prediction_available' => true, 'prediction_period' => '123457', 'period' => ['cperiod' => '123456',
                    'period' => '123457', 'open_time' => 210, 'time' => '2026-10-19 12:00:00', 'next_time_end' => '2026-10-19 12:03:30']];
        } else {
            $positions = $racing ? array_map(static fn (int $n): string => '第' . $n . '名', range(1, 10))
                : ['万位', '千位', '百位', '十位', '个位'];
            $range = range($definition['min'], $definition['max']);
            $analysis = [
                ['key' => 'sum', 'label' => $racing ? '冠亚和' : '总和', 'field' => 'sum', 'values' => range($racing ? 3 : 0, $racing ? 19 : 45)],
                ['key' => 'size', 'label' => '大小', 'field' => 'size', 'values' => ['大', '小']],
                ['key' => 'parity', 'label' => '单双', 'field' => 'parity', 'values' => ['单', '双']],
            ];
            foreach ($positions as $index => $label) {
                $analysis[] = ['key' => 'position-' . $index, 'label' => $label, 'field' => 'balls', 'position' => $index, 'values' => $range];
            }
            $data = ['game' => ['code' => $code, 'name' => $definition['name'], 'type' => $definition['type'],
                'positions' => $positions, 'range' => $range, 'sum_label' => $racing ? '冠亚和' : '总和',
                'analysis' => $analysis, 'rule' => '测试号码规则'], 'list' => [$row],
                'period' => ['server_time' => 1792382400, 'next_draw_at' => 1792382610, 'interval_seconds' => 210],
                'collection' => ['attempt_time' => '2026-10-19 12:00:00', 'success_time' => '2026-10-19 12:00:00', 'unavailable' => false]];
        }
        return ['version' => 1, 'code' => 200, 'data' => $data];
    }

    /** 历史协议只包含公开行和快照，不传站点配置。 */
    private function history(string $code = 'jnd28'): array
    {
        $latest = $this->latest($code);
        return ['version' => 1, 'code' => 200, 'data' => ['game' => ['code' => $code], 'list' => $latest['data']['list'],
            'pagination' => ['page' => 1, 'page_size' => 20, 'total' => 1, 'total_pages' => 1, 'snapshot' => 123]]];
    }

    /** 编码可控响应，测试输入保持与真实 HTTP 完全一致。 */
    private function response(array $envelope): array
    {
        return ['status' => 200, 'body' => json_encode($envelope, JSON_THROW_ON_ERROR)];
    }

    /** 校验七个彩种两种数据形态，并剔除额外中心广告与内部配置。 */
    public function testEveryGameLatestAndHistoryAreValidatedAndFiltered(): void
    {
        foreach (array_keys(FeedCatalog::GAMES) as $code) {
            foreach (['latest', 'history'] as $resource) {
                $envelope = $resource === 'latest' ? $this->latest($code) : $this->history($code);
                $envelope['data']['ads'] = ['private'];
                $envelope['data']['token'] = 'should-not-pass';
                $envelope['data']['list'][0]['source'] = 'private-source';
                $params = ['game' => $code] + ($resource === 'history' ? ['page' => 1, 'page_size' => 20, 'snapshot' => 0] : []);
                $data = SharedDataPayload::validate($envelope, $resource, $params);
                self::assertSame($code, $data['game']['code']);
                self::assertCount(1, $data['list']);
                self::assertArrayNotHasKey('ads', $data);
                self::assertArrayNotHasKey('token', $data);
                self::assertArrayNotHasKey('source', $data['list'][0]);
            }
        }
    }

    /** 快照必须与请求相符，页大小和总数不能伪造。 */
    public function testHistoryRejectsWrongSnapshotAndInconsistentPagination(): void
    {
        $params = ['game' => 'jnd28', 'page' => 1, 'page_size' => 20, 'snapshot' => 123];
        foreach (['snapshot' => 124, 'page_size' => 50, 'total' => 2, 'total_pages' => 2, 'page' => 2] as $field => $value) {
            $bad = $this->history();
            $bad['data']['pagination'][$field] = $value;
            try {
                SharedDataPayload::validate($bad, 'history', $params);
                self::fail('无效分页应被拒绝：' . $field);
            } catch (UnexpectedValueException) { self::assertTrue(true); }
        }
    }

    /** 响应不允许串彩种、错号、重复期号、错误分类、无效时间或伪造预测命中。 */
    public function testMalformedDataIsRejected(): void
    {
        $mutations = [
            static function (array &$x): void { $x['version'] = 2; },
            static function (array &$x): void { $x['data']['game']['code'] = 'az10'; },
            static function (array &$x): void { $x['data']['list'][0]['code_arr'][0] = '1'; },
            static function (array &$x): void { $x['data']['list'][0]['code_arr'][0] = 10; },
            static function (array &$x): void { $x['data']['list'][0]['sum'] = 7; },
            static function (array &$x): void { $x['data']['list'][0]['dx'] = '大'; },
            static function (array &$x): void { $x['data']['list'][0]['ds'] = '单'; },
            static function (array &$x): void { $x['data']['list'][0]['created'] = '2026-02-30 12:00:00'; },
            static function (array &$x): void { $x['data']['list'][0]['period'] = '<script>'; },
            static function (array &$x): void { $x['data']['list'][0]['prds'] = 1; },
            static function (array &$x): void { $x['data']['list'][0]['pdx'] = '<img>'; },
            static function (array &$x): void { $x['data']['list'][0]['prediction_available'] = 'true'; },
            static function (array &$x): void { $x['data']['list'][] = $x['data']['list'][0]; },
            static function (array &$x): void { $x['data']['list'] = array_fill(0, 101, $x['data']['list'][0]); },
            static function (array &$x): void { $x['data']['period']['cperiod'] = '654321'; },
            static function (array &$x): void { $x['data']['prediction']['pbdxds'] = '大双'; },
        ];
        foreach ($mutations as $mutate) {
            $bad = $this->latest();
            $mutate($bad);
            try {
                SharedDataPayload::validate($bad, 'latest', ['game' => 'jnd28']);
                self::fail('无效响应应被拒绝。');
            } catch (UnexpectedValueException) { self::assertTrue(true); }
        }
        $bad = $this->latest('az10');
        $bad['data']['list'][0]['balls'][0] = 2;
        $this->expectException(UnexpectedValueException::class);
        SharedDataPayload::validate($bad, 'latest', ['game' => 'az10']);
    }

    /** 固定路由和服务端认证头，二秒内重复请求只调用中心一次。 */
    public function testFreshCacheAndAuthenticatedRequest(): void
    {
        $calls = 0;
        $gateway = new SharedDataGateway($this->config(), function (string $url, array $headers) use (&$calls): array {
            $calls++;
            self::assertSame('https://center.example/index.php?r=shared-data%2Flatest&game=jnd28', $url);
            self::assertContains('X-Lottery-Client: unit-client', $headers);
            self::assertContains('Authorization: Bearer ' . str_repeat('T', 64), $headers);
            return $this->response($this->latest());
        }, new ArrayCache());
        $first = $gateway->fetch('latest', ['game' => 'jnd28']);
        $second = $gateway->fetch('latest', ['game' => 'jnd28']);
        self::assertSame($first, $second);
        self::assertSame(1, $calls);
        self::assertFalse($first['shared_data']['stale']);
        self::assertIsInt($first['shared_data']['fetched_at']);
    }

    /** 中心地址、客户身份或密钥不同都不得复用原有缓存。 */
    public function testCacheIsSeparatedByEndpointClientAndToken(): void
    {
        $cache = new ArrayCache();
        $calls = 0;
        foreach ([[], ['endpoint' => 'https://other.example/index.php'], ['client_id' => 'other-client'], ['token' => str_repeat('X', 64)]] as $change) {
            $gateway = new SharedDataGateway($this->config($change), function () use (&$calls): array {
                $calls++;
                return $this->response($this->latest());
            }, $cache);
            $gateway->fetch('latest', ['game' => 'jnd28']);
        }
        self::assertSame(4, $calls);
    }

    /** 网络和暂时性错误保留已验证数据，标记旧值并合并两秒内的失败重试。 */
    public function testTemporaryFailuresUseStaleDataWithoutExtendingItsTimestamp(): void
    {
        foreach ([0, 429, 500, 503] as $status) {
            $calls = 0;
            $gateway = new SharedDataGateway($this->config(['cache_seconds' => 0]), function () use (&$calls, $status): array {
                if (++$calls === 1) { return $this->response($this->latest()); }
                if ($status === 0) { throw new RuntimeException('transport details must not escape'); }
                return ['status' => $status, 'body' => 'private upstream diagnostic'];
            }, new ArrayCache());
            $first = $gateway->fetch('latest', ['game' => 'jnd28']);
            $stale = $gateway->fetch('latest', ['game' => 'jnd28']);
            self::assertTrue($stale['shared_data']['stale']);
            self::assertSame($first['shared_data']['fetched_at'], $stale['shared_data']['fetched_at']);
            self::assertSame($first['list'], $stale['list']);
            $gateway->fetch('latest', ['game' => 'jnd28']);
            self::assertSame(2, $calls);
        }
    }

    /** 非法 JSON、超量和错误协议均不能污染成功缓存。 */
    public function testInvalidResponsesFallBackToValidatedCache(): void
    {
        foreach (['not json', str_repeat('x', 2097153), '{"version":1,"code":200,"data":{}}', 'null'] as $body) {
            $calls = 0;
            $gateway = new SharedDataGateway($this->config(['cache_seconds' => 0]), function () use (&$calls, $body): array {
                return ++$calls === 1 ? $this->response($this->latest()) : ['status' => 200, 'body' => $body];
            }, new ArrayCache());
            $first = $gateway->fetch('latest', ['game' => 'jnd28']);
            $stale = $gateway->fetch('latest', ['game' => 'jnd28']);
            self::assertTrue($stale['shared_data']['stale']);
            self::assertSame($first['list'], $stale['list']);
        }
    }

    /** 认证、撤销、未找到及重定向不允许读旧缓存，原始错误正文也不可返回。 */
    public function testAuthorizationAndOtherPermanentFailuresNeverUseStaleCache(): void
    {
        foreach ([401, 403, 404, 400, 301, 302] as $status) {
            $calls = 0;
            $gateway = new SharedDataGateway($this->config(['cache_seconds' => 0]), function () use (&$calls, $status): array {
                return ++$calls === 1 ? $this->response($this->latest()) : ['status' => $status, 'body' => 'private-token-diagnostic'];
            }, new ArrayCache());
            $gateway->fetch('latest', ['game' => 'jnd28']);
            try {
                $gateway->fetch('latest', ['game' => 'jnd28']);
                self::fail('永久失败不应使用缓存：' . $status);
            } catch (HttpException $exception) {
                self::assertSame(503, $exception->statusCode);
                self::assertStringNotContainsString('private-token', $exception->getMessage());
            }
        }
    }

    /** 没有任何成功结果时，网络故障不返回伪造的空开奖成功响应。 */
    public function testNoSuccessfulCacheReturns503(): void
    {
        $gateway = new SharedDataGateway($this->config(), static fn (): array => ['status' => 503, 'body' => ''], new ArrayCache());
        $this->expectException(HttpException::class);
        $gateway->fetch('latest', ['game' => 'jnd28']);
    }

    /** 底层缓存尚未清理时也不能使用超过允许时长的数据。 */
    public function testExpiredCacheCannotBeServedDuringOutage(): void
    {
        $cache = new class extends ArrayCache {
            public bool $expireResults = false;
            /** 模拟底层保留超龄条目，验证网关自身的有效期边界。 */
            public function get($key): mixed
            {
                $value = parent::get($key);
                if ($this->expireResults && is_array($value) && isset($value['fetched_microtime'])) {
                    $value['fetched_microtime'] = microtime(true) - 1801;
                }
                return $value;
            }
        };
        $calls = 0;
        $gateway = new SharedDataGateway($this->config(), function () use (&$calls): array {
            return ++$calls === 1 ? $this->response($this->latest()) : ['status' => 503, 'body' => ''];
        }, $cache);
        $gateway->fetch('latest', ['game' => 'jnd28']);
        $cache->expireResults = true;
        $this->expectException(HttpException::class);
        $gateway->fetch('latest', ['game' => 'jnd28']);
    }

    /** 取得认证失败后，别的资源即使已有旧缓存也不能借网络故障继续显示。 */
    public function testRevocationPreventsCrossResourceStaleFallback(): void
    {
        $calls = 0;
        $gateway = new SharedDataGateway($this->config(['cache_seconds' => 0]), function (string $url) use (&$calls): array {
            if (++$calls === 1) { return $this->response($this->latest()); }
            return ['status' => str_contains($url, 'history') ? 403 : 503, 'body' => ''];
        }, new ArrayCache());
        $gateway->fetch('latest', ['game' => 'jnd28']);
        try {
            $gateway->fetch('history', ['game' => 'jnd28', 'page' => 1, 'page_size' => 20, 'snapshot' => 0]);
            self::fail('撤销应失败。');
        } catch (HttpException) { self::assertTrue(true); }
        $this->expectException(HttpException::class);
        $gateway->fetch('latest', ['game' => 'jnd28']);
    }

    /** 过滤请求字段并拒绝未知路由或不可控分页，不允许通过输入改中心地址。 */
    public function testRequestParametersAreStrict(): void
    {
        $bad = [['latest', ['game' => 'bad']], ['latest', ['game' => 'jnd28', 'url' => 'https://evil.test']],
            ['../latest', ['game' => 'jnd28']], ['history', ['game' => 'jnd28', 'page' => 0, 'page_size' => 20, 'snapshot' => 0]],
            ['history', ['game' => 'jnd28', 'page' => 1, 'page_size' => 21, 'snapshot' => 0]],
            ['history', ['game' => 'jnd28', 'page' => [], 'page_size' => 20, 'snapshot' => 0]],
            ['history', ['game' => 'jnd28', 'page' => 1, 'page_size' => 20, 'snapshot' => 9007199254740992]]];
        foreach ($bad as [$resource, $params]) {
            try {
                SharedDataPayload::parameters($resource, $params);
                self::fail('应拒绝非法参数。');
            } catch (InvalidArgumentException) { self::assertTrue(true); }
        }
        self::assertSame(['game' => 'jnd28', 'page' => 1, 'page_size' => 20, 'snapshot' => 123],
            SharedDataPayload::parameters('history', ['game' => 'jnd28', 'page' => '1', 'page_size' => '20', 'snapshot' => '123']));
    }
}
