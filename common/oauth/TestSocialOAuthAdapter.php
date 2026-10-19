<?php

/**
 * 第三方登录内部测试适配器。
 *
 * 该适配器只允许 dev/test 配置使用，用于验证 OAuth 状态、回调、
 * 一次性票据与会员绑定链路；生产环境禁止启用。
 */

namespace common\oauth;

use Yii;

final class TestSocialOAuthAdapter implements SocialOAuthAdapterInterface
{
    /** @var int 社交平台编码。 */
    private int $provider;

    /** @var array<string, mixed> 当前平台配置。 */
    private array $config;

    /** @var string|null 最近一次错误。 */
    private ?string $error = null;

    /**
     * 创建内部测试适配器。
     *
     * @param int $provider 社交平台编码
     * @param array<string, mixed> $config 当前平台配置
     */
    public function __construct(int $provider, array $config)
    {
        $this->provider = $provider;
        $this->config = $config;
    }

    /**
     * 生成模拟第三方授权确认页地址。
     *
     * @param string $state 服务端签发的一次性防伪状态值
     * @return string|false 授权确认页地址
     */
    public function buildAuthorizeUrl(string $state): string|false
    {
        $url = trim((string) ($this->config['testGrantUrl'] ?? ''));
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return $this->fail('内部测试授权地址未配置。');
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query([
            'state' => $state,
        ]);
    }

    /**
     * 校验内部测试授权码并返回服务端生成的身份资料。
     *
     * @param string $code 内部测试授权码
     * @param string $state 服务端已校验的状态值
     * @return array{providerAppId: string, providerUserId: string, nickname: string, avatar: string}|false
     */
    public function exchangeCode(string $code, string $state): array|false
    {
        $key = 'oauth:test-code:' . hash('sha256', $code);
        $payload = Yii::$app->cache->get($key);
        Yii::$app->cache->delete($key);
        if (!is_array($payload)) {
            return $this->fail('测试授权码无效或已过期。');
        }
        if (
            (int) ($payload['provider'] ?? 0) !== $this->provider
            || !hash_equals((string) ($payload['state'] ?? ''), $state)
        ) {
            return $this->fail('测试授权码与当前授权请求不匹配。');
        }

        $appId = trim((string) ($this->config['appId'] ?? ''));
        $userId = trim((string) ($payload['providerUserId'] ?? ''));
        if ($appId === '' || $userId === '') {
            return $this->fail('测试平台身份配置无效。');
        }

        return [
            'providerAppId' => $appId,
            'providerUserId' => $userId,
            'nickname' => (string) ($payload['nickname'] ?? '测试会员'),
            'avatar' => '',
        ];
    }

    /**
     * 返回最近一次测试授权错误。
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * 记录失败原因并返回 false。
     *
     * @param string $message 可展示错误信息
     */
    private function fail(string $message): false
    {
        $this->error = $message;
        return false;
    }
}
