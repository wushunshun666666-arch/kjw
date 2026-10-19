<?php

namespace common\models\lottery;

use yii\caching\CacheInterface;
use yii\mutex\FileMutex;
use yii\web\HttpException;

/** 客户服务器到中心的只读适配器，隔离客户缓存、合并并发且绝不写入业务数据库。 */
final class SharedDataGateway
{
    private const MAX_BYTES = 2097152;
    private SharedDataConfig $config;
    private \Closure $transport;
    private CacheInterface $cache;

    /**
     * 注入固定配置、HTTP 传输和缓存，便于独立验证故障、授权及快照行为。
     *
     * @param callable(string,array):array{status:int,body:string}|null $transport 网络故障可抛 RuntimeException
     */
    public function __construct(?SharedDataConfig $config = null, ?callable $transport = null, ?CacheInterface $cache = null)
    {
        $this->config = $config ?? SharedDataConfig::current();
        $this->transport = $transport === null ? $this->request(...) : \Closure::fromCallable($transport);
        $this->cache = $cache ?? \Yii::$app->get('cache');
    }

    /** 按固定协议读取中心数据，仅网络、限流、服务异常和无效响应可以使用未过期旧缓存。 */
    public function fetch(string $resource, array $params): array
    {
        if (!$this->config->isClient()) {
            throw new \LogicException('共享开奖网关只适用于客户端模式。');
        }
        $params = SharedDataPayload::parameters($resource, $params);
        $identity = hash('sha256', $this->config->endpoint() . "\n" . $this->config->clientId() . "\n" . $this->config->token());
        $key = ['lottery-shared-v1', $identity, $resource, $params];
        $cached = $this->cached($key);
        $revokedKey = ['lottery-shared-auth-denied-v1', $identity];
        if ($this->fresh($cached) && !$this->cache->get($revokedKey)) {
            return $this->result($cached, false);
        }
        $mutex = new FileMutex(['mutexPath' => '@common/runtime/shared-data-mutex', 'dirMode' => 0700, 'fileMode' => 0600]);
        $lock = 'lottery-shared-' . hash('sha256', json_encode($key, JSON_THROW_ON_ERROR));
        if (!$mutex->acquire($lock, 4)) {
            $cached = $this->cached($key);
            if ($this->fresh($cached) && !$this->cache->get($revokedKey)) { return $this->result($cached, false); }
            return $this->fallback($cached, !$this->cache->get($revokedKey));
        }
        try {
            $cached = $this->cached($key);
            if ($this->fresh($cached) && !$this->cache->get($revokedKey)) { return $this->result($cached, false); }
            $failureKey = ['lottery-shared-failure-v1', $key];
            $failure = $this->cache->get($failureKey);
            if (is_array($failure) && ($failure['at'] ?? 0) > microtime(true) - 2) {
                return $this->fallback($cached, ($failure['fallback'] ?? false) && !$this->cache->get($revokedKey));
            }
            $url = $this->config->endpoint() . '?' . http_build_query(['r' => 'shared-data/' . $resource] + $params, '', '&', PHP_QUERY_RFC3986);
            $headers = ['Accept: application/json', 'X-Lottery-Client: ' . $this->config->clientId(), 'Authorization: Bearer ' . $this->config->token()];
            try {
                $response = ($this->transport)($url, $headers);
            } catch (\RuntimeException) {
                $response = ['status' => 0, 'body' => ''];
            }
            $status = is_array($response) && is_int($response['status'] ?? null) ? $response['status'] : 0;
            $fallback = $status === 0 || $status === 429 || $status >= 500;
            if ($status === 200) {
                try {
                    if (!is_string($response['body'] ?? null) || strlen($response['body']) > self::MAX_BYTES) {
                        throw new \UnexpectedValueException('共享开奖中心响应大小无效。');
                    }
                    $envelope = json_decode($response['body'], true, 64, JSON_THROW_ON_ERROR);
                    if (!is_array($envelope)) { throw new \UnexpectedValueException('共享开奖中心响应无效。'); }
                    $data = SharedDataPayload::validate($envelope, $resource, $params);
                    $success = ['data' => $data, 'fetched_at' => time(), 'fetched_microtime' => microtime(true)];
                    $this->cache->set($key, $success, $this->config->staleSeconds());
                    $this->cache->delete($revokedKey);
                    $this->cache->delete($failureKey);
                    return $this->result($success, false);
                } catch (\JsonException | \UnexpectedValueException) {
                    $fallback = true;
                }
            }
            if (in_array($status, [401, 403], true)) {
                $this->cache->set($revokedKey, true, $this->config->staleSeconds());
            }
            if (!$fallback) {
                $this->cache->delete($key);
                $cached = null;
            }
            $this->cache->set($failureKey, ['at' => microtime(true), 'fallback' => $fallback], 2);
            return $this->fallback($cached, $fallback && !$this->cache->get($revokedKey));
        } finally {
            $mutex->release($lock);
        }
    }

    /** 缓存必须来自当前版本和成功请求，超龄内容即使底层未清理也不得继续展示。 */
    private function cached(array $key): ?array
    {
        $entry = $this->cache->get($key);
        return is_array($entry) && is_array($entry['data'] ?? null) && is_int($entry['fetched_at'] ?? null)
            && is_float($entry['fetched_microtime'] ?? null) && $entry['fetched_microtime'] <= microtime(true)
            && microtime(true) - $entry['fetched_microtime'] < $this->config->staleSeconds() ? $entry : null;
    }

    /** 短缓存与故障旧缓存分别计算，旧结果不能因被读取而延长生命周期。 */
    private function fresh(?array $entry): bool
    {
        return $entry !== null && microtime(true) - $entry['fetched_microtime'] < $this->config->cacheSeconds();
    }

    /** 只有允许的临时故障才能降级；不存在成功缓存时返回统一503。 */
    private function fallback(?array $entry, bool $allowed): array
    {
        if ($entry !== null && $allowed) { return $this->result($entry, true); }
        throw new HttpException(503, '共享开奖数据暂时不可用，请稍后重试。');
    }

    /** 在独立字段明确标记旧数据以及实际最后取得时间，不伪装实时采集。 */
    private function result(array $entry, bool $stale): array
    {
        return $entry['data'] + ['shared_data' => ['stale' => $stale, 'fetched_at' => $entry['fetched_at']]];
    }

    /** 固定请求超时、体积和 TLS 校验，绝不跟随可能带走凭据的重定向。 */
    private function request(string $url, #[\SensitiveParameter] array $headers): array
    {
        $body = '';
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > self::MAX_BYTES) { return 0; }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($handle);
        return ['status' => $ok === false ? 0 : (int) curl_getinfo($handle, CURLINFO_HTTP_CODE), 'body' => $body];
    }
}
