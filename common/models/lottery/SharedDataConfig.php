<?php

namespace common\models\lottery;

use yii\base\InvalidConfigException;

/** 三种开奖部署模式及服务器私有凭据，不读取请求或公开站点配置。 */
final class SharedDataConfig
{
    private array $values;

    /** 验证部署配置；错误仅说明字段，不在异常或日志中包含配置值。 */
    public function __construct(#[\SensitiveParameter] array $values = [])
    {
        $defaults = ['mode' => 'standalone', 'endpoint' => '', 'client_id' => '', 'token' => '',
            'clients' => [], 'cache_seconds' => 2, 'stale_seconds' => 1800];
        if (array_diff_key($values, $defaults) !== []) {
            throw new InvalidConfigException('共享开奖配置包含未知字段。');
        }
        $values += $defaults;
        if (!in_array($values['mode'], ['standalone', 'center', 'client'], true)) {
            throw new InvalidConfigException('共享开奖 mode 无效。');
        }
        foreach (['endpoint', 'client_id', 'token'] as $field) {
            if (!is_string($values[$field])) {
                throw new InvalidConfigException('共享开奖配置字段类型无效。');
            }
        }
        if ($values['endpoint'] !== '' && !self::validEndpoint($values['endpoint'])) {
            throw new InvalidConfigException('共享开奖 endpoint 必须是 HTTPS index.php 地址；本机测试允许 HTTP。');
        }
        if ($values['client_id'] !== '' && !self::validClientId($values['client_id'])) {
            throw new InvalidConfigException('共享开奖 client_id 格式无效。');
        }
        if ($values['token'] !== '' && !preg_match('/^[a-zA-Z0-9._~-]{32,128}$/D', $values['token'])) {
            throw new InvalidConfigException('共享开奖 token 必须为32至128位字母、数字或 . _ ~ - 字符。');
        }
        if ($values['mode'] === 'client' && ($values['endpoint'] === '' || $values['client_id'] === '' || $values['token'] === '')) {
            throw new InvalidConfigException('客户端模式必须配置 endpoint、client_id 和 token。');
        }
        if (!is_array($values['clients'])) {
            throw new InvalidConfigException('共享开奖 clients 必须为客户映射。');
        }
        foreach ($values['clients'] as $id => $client) {
            if (!self::validClientId((string) $id) || !is_array($client)
                || array_diff_key($client, ['token_hash' => '', 'enabled' => true]) !== []
                || !is_string($client['token_hash'] ?? null)
                || !preg_match('/^[a-f0-9]{64}$/D', $client['token_hash'])
                || !is_bool($client['enabled'] ?? null)) {
                throw new InvalidConfigException('共享开奖 clients 项格式无效，必须提供 SHA-256 token_hash 和布尔 enabled。');
            }
        }
        if (!is_int($values['cache_seconds']) || $values['cache_seconds'] < 0 || $values['cache_seconds'] > 2) {
            throw new InvalidConfigException('共享开奖 cache_seconds 必须为0至2秒。');
        }
        if (!is_int($values['stale_seconds']) || $values['stale_seconds'] < max(1, $values['cache_seconds'])
            || $values['stale_seconds'] > 86400) {
            throw new InvalidConfigException('共享开奖 stale_seconds 必须为1至86400秒且不小于缓存时长。');
        }
        $this->values = $values;
    }

    /** 显式实例参数优先，随后读取被 Git 忽略的私有部署文件；缺省保留原单站模式。 */
    public static function current(): self
    {
        if (\Yii::$app !== null && array_key_exists('lotteryData', \Yii::$app->params)) {
            $values = \Yii::$app->params['lotteryData'];
        } else {
            $file = dirname(__DIR__, 2) . '/config/lottery-data-local.php';
            try {
                $values = is_file($file) ? require $file : [];
            } catch (\Throwable) {
                throw new InvalidConfigException('共享开奖私有配置无法加载，请检查服务器部署文件。');
            }
        }
        if (!is_array($values)) {
            throw new InvalidConfigException('共享开奖部署配置必须返回数组。');
        }
        return new self($values);
    }

    /** 返回当前部署角色，供后台状态说明使用。 */
    public function mode(): string { return $this->values['mode']; }

    /** 客户端只能读取中心数据，不能运行本地采集。 */
    public function isClient(): bool { return $this->mode() === 'client'; }

    /** 仅中心模式允许对已授权客户开放共享接口。 */
    public function isCenter(): bool { return $this->mode() === 'center'; }

    /** 返回固定中心入口，不允许访客覆盖。 */
    public function endpoint(): string { return $this->values['endpoint']; }

    /** 返回当前客户身份，仅用于服务端请求。 */
    public function clientId(): string { return $this->values['client_id']; }

    /** 返回当前客户密钥，禁止输出到日志或浏览器。 */
    public function token(): string { return $this->values['token']; }

    /** 返回中心允许的客户及密钥摘要，不保存明文客户密钥。 */
    public function clients(): array { return $this->values['clients']; }

    /** 最多缓存2秒，限制中心撤销客户后的继续读取窗口。 */
    public function cacheSeconds(): int { return $this->values['cache_seconds']; }

    /** 网络故障时最后一次成功结果可保留的总时长。 */
    public function staleSeconds(): int { return $this->values['stale_seconds']; }

    /** 客户编号不能注入 HTTP Header 或越界扩大缓存键。 */
    private static function validClientId(string $id): bool
    {
        return preg_match('/^[a-zA-Z0-9_-]{1,64}$/D', $id) === 1;
    }

    /** 固定 HTTPS 入口且禁止凭据、查询和片段，避免路由覆盖与密钥发送到重定向地址。 */
    private static function validEndpoint(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false || strlen($url) > 2048 || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            || isset($parts['user'], $parts['pass']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment']) || empty($parts['host'])
            || !str_ends_with($parts['path'] ?? '', '/index.php')) {
            return false;
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'], '[]'));
        return $scheme === 'https' || ($scheme === 'http' && in_array($host, ['127.0.0.1', 'localhost', '::1'], true));
    }
}
