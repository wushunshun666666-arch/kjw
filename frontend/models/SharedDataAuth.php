<?php

namespace frontend\models;

use common\models\lottery\SharedDataConfig;
use yii\caching\CacheInterface;
use yii\caching\FileCache;
use yii\mutex\FileMutex;
use yii\mutex\Mutex;
use yii\web\NotFoundHttpException;
use yii\web\HttpException;
use yii\web\UnauthorizedHttpException;

/** 中心共享接口的客户鉴权及跨请求限流，不读取 Cookie 或查询参数。 */
final class SharedDataAuth
{
    public const REQUESTS_PER_MINUTE = 240;

    private SharedDataConfig $config;
    private ?CacheInterface $cache;
    private ?Mutex $mutex;

    /** 注入实例配置与限流存储，测试无需创建真实客户或修改私有配置。 */
    public function __construct(?SharedDataConfig $config = null, ?CacheInterface $cache = null, ?Mutex $mutex = null)
    {
        $this->config = $config ?? SharedDataConfig::current();
        $this->cache = $cache;
        $this->mutex = $mutex;
    }

    /** 只验证中心模式下的请求头凭据，未知、停用和错误凭据返回相同结果。 */
    public function authenticate(mixed $clientId, #[\SensitiveParameter] mixed $authorization): string
    {
        if (!$this->config->isCenter()) {
            throw new NotFoundHttpException('接口不存在。');
        }
        if (!is_string($clientId) || !preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $clientId)
            || !is_string($authorization) || !preg_match('/^Bearer ([a-zA-Z0-9._~-]{32,128})$/Di', $authorization, $matches)) {
            throw new UnauthorizedHttpException('共享接口凭据无效。');
        }
        $client = $this->config->clients()[$clientId] ?? [];
        $hash = $client['token_hash'] ?? str_repeat('0', 64);
        $matchesToken = is_string($hash) && hash_equals($hash, hash('sha256', $matches[1]));
        if (!$matchesToken || ($client['enabled'] ?? false) !== true) {
            throw new UnauthorizedHttpException('共享接口凭据无效。');
        }
        return $clientId;
    }

    /**
     * 在文件互斥锁内消耗客户当分钟配额；存储或锁失败时停止放行。
     *
     * @return int 已放行返回0，超额返回距离下一分钟的秒数
     */
    public function consumeQuota(string $clientId, ?int $now = null): int
    {
        $now ??= time();
        $bucket = intdiv($now, 60);
        $key = ['shared-data-rate-v1', $clientId, $bucket];
        $lock = 'shared-data-rate-' . hash('sha256', $clientId);
        $cache = $this->cache ??= new FileCache(['cachePath' => '@runtime/shared-data-rate-cache']);
        $mutex = $this->mutex ??= new FileMutex(['mutexPath' => '@runtime/shared-data-rate-locks']);
        if (!$mutex->acquire($lock, 1)) {
            throw new HttpException(503, '共享服务暂时不可用。');
        }
        try {
            $count = $cache->get($key);
            if ($count !== false && (!is_int($count) || $count < 0)) {
                throw new HttpException(503, '共享服务暂时不可用。');
            }
            if ($count !== false && $count >= self::REQUESTS_PER_MINUTE) {
                return 60 - $now % 60;
            }
            if (!$cache->set($key, ($count === false ? 0 : $count) + 1, 120)) {
                throw new HttpException(503, '共享服务暂时不可用。');
            }
            return 0;
        } finally {
            $mutex->release($lock);
        }
    }
}
