<?php

use common\models\lottery\SharedDataConfig;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;

/** 部署配置验证不读取真实密钥，不改变现有私有部署文件。 */
final class SharedDataConfigTest extends TestCase
{
    /** 默认模式保持原有单站采集，中心客户只保存密钥摘要。 */
    public function testStandaloneDefaultsAndCenterClientMap(): void
    {
        $default = new SharedDataConfig();
        self::assertSame('standalone', $default->mode());
        self::assertFalse($default->isClient());
        self::assertFalse($default->isCenter());
        self::assertSame(2, $default->cacheSeconds());
        self::assertSame(1800, $default->staleSeconds());
        $clients = ['customer_a' => ['token_hash' => hash('sha256', 'unit-test-only'), 'enabled' => false]];
        $center = new SharedDataConfig(['mode' => 'center', 'clients' => $clients]);
        self::assertTrue($center->isCenter());
        self::assertSame($clients, $center->clients());
    }

    /** 实例参数优先且没有静态记忆，测试切换角色不会影响下一次读取。 */
    public function testCurrentUsesInstanceParamsWithoutMemoization(): void
    {
        $had = array_key_exists('lotteryData', Yii::$app->params);
        $old = Yii::$app->params['lotteryData'] ?? null;
        try {
            Yii::$app->params['lotteryData'] = ['mode' => 'center'];
            self::assertTrue(SharedDataConfig::current()->isCenter());
            Yii::$app->params['lotteryData'] = [];
            self::assertSame('standalone', SharedDataConfig::current()->mode());
            Yii::$app->params['lotteryData'] = 'bad';
            $this->expectException(InvalidConfigException::class);
            SharedDataConfig::current();
        } finally {
            if ($had) { Yii::$app->params['lotteryData'] = $old; } else { unset(Yii::$app->params['lotteryData']); }
        }
    }

    /** HTTPS 生产入口和明确的三个本机 HTTP 地址被允许。 */
    public function testClientEndpointAllowlist(): void
    {
        foreach (['https://center.example/index.php', 'https://center.example/sub/index.php',
            'http://127.0.0.1:8097/index.php', 'http://localhost/index.php', 'http://[::1]/index.php'] as $endpoint) {
            $config = new SharedDataConfig(['mode' => 'client', 'endpoint' => $endpoint, 'client_id' => 'client_1', 'token' => str_repeat('Q', 64)]);
            self::assertTrue($config->isClient());
            self::assertSame($endpoint, $config->endpoint());
            self::assertSame('client_1', $config->clientId());
        }
    }

    /** 拼写错误、弱密钥、Header 注入、明文远端地址与不合法客户配置必须立即报错。 */
    public function testMalformedConfigurationIsRejectedWithoutSecretInErrors(): void
    {
        $bad = [['mode' => 'clients'], ['unknown' => 'secret-value'], ['mode' => 'client'], ['endpoint' => []],
            ['endpoint' => 'http://remote.example/index.php'], ['endpoint' => 'https://center.example/index.php?token=secret-value'],
            ['endpoint' => 'https://user:secret-value@center.example/index.php'], ['endpoint' => 'https://center.example/index.php#fragment'],
            ['endpoint' => 'https://center.example/other.php'], ['endpoint' => 'http://127.0.0.2/index.php'],
            ['client_id' => "customer\r\nInjected: secret-value"], ['token' => 'secret-value'],
            ['clients' => ['customer' => ['token_hash' => str_repeat('x', 64), 'enabled' => true]]],
            ['clients' => ['customer' => ['token_hash' => str_repeat('a', 64), 'enabled' => 'true']]],
            ['clients' => ['customer' => ['token_hash' => str_repeat('a', 64), 'enabled' => true, 'token' => 'secret-value']]],
            ['cache_seconds' => 3], ['cache_seconds' => '2'], ['stale_seconds' => 0], ['stale_seconds' => 86401]];
        foreach ($bad as $values) {
            try {
                new SharedDataConfig($values);
                self::fail('无效配置必须被拒绝。');
            } catch (InvalidConfigException $exception) {
                self::assertStringNotContainsString('secret-value', $exception->getMessage());
            }
        }
    }
}
