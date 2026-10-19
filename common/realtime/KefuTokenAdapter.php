<?php

/**
 * 客服实时服务JWT适配器。
 *
 * 集中负责短期Token的签发、固定算法校验和按kid选择轮换密钥。
 */

namespace common\realtime;

use mylibs\security\Jwt;
use mylibs\yii\config\Configure;
use RuntimeException;

/** 客服实时服务与JWT组件之间的安全适配边界。 */
final class KefuTokenAdapter
{
    /** Token签发方。 */
    public const ISSUER = 'zhonghepan';

    /** 客服实时服务受众。 */
    public const AUDIENCE = 'kefu-realtime';

    /** 唯一允许的JWT签名算法。 */
    private const ALGORITHM = 'HS256';

    /**
     * 签发只允许客服实时服务使用的一小时短期Token。
     *
     * @param array<string, mixed> $claims 已由业务层确定的身份与频道声明
     */
    public static function issue(array $claims): string
    {
        $config = self::getConfig();
        $issuedAt = time();
        $payload = array_merge([
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'nbf' => $issuedAt,
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
            'jti' => bin2hex(random_bytes(16)),
        ], $claims);

        return Jwt::encode($payload, $config['signingKey'], self::ALGORITHM, $config['keyId']);
    }

    /**
     * 校验签名和通用注册声明并返回可信载荷。
     *
     * @return array<string, mixed>|null Token无效时返回null
     */
    public static function decode(string $token): ?array
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            return null;
        }
        [$headerSegment, $payloadSegment, $signatureSegment] = $segments;
        $header = self::decodeJsonSegment($headerSegment);
        $payload = self::decodeJsonSegment($payloadSegment);
        if ($header === null || $payload === null || ($header['alg'] ?? '') !== self::ALGORITHM) {
            return null;
        }

        $config = self::getConfig();
        $keys = [$config['keyId'] => $config['signingKey']] + $config['previousKeys'];
        $keyId = is_string($header['kid'] ?? null) ? $header['kid'] : '';
        $key = $keys[$keyId] ?? null;
        $signature = self::base64UrlDecode($signatureSegment);
        if (!is_string($key) || $key === '' || $signature === null) {
            return null;
        }

        $expected = hash_hmac('sha256', $headerSegment . '.' . $payloadSegment, $key, true);
        $now = time();
        if (
            !hash_equals($expected, $signature)
            || ($payload['iss'] ?? '') !== self::ISSUER
            || ($payload['aud'] ?? '') !== self::AUDIENCE
            || !is_string($payload['jti'] ?? null)
            || $payload['jti'] === ''
            || (int) ($payload['nbf'] ?? 0) > $now
            || (int) ($payload['exp'] ?? 0) <= $now
        ) {
            return null;
        }

        return $payload;
    }

    /**
     * 读取并验证当前环境的签名和轮换密钥配置。
     *
     * @return array{keyId: string, signingKey: string, previousKeys: array<string, string>}
     */
    private static function getConfig(): array
    {
        $config = (new Configure())->loadCommonConfig('kefu-realtime');
        $keyId = trim((string) ($config['keyId'] ?? ''));
        $signingKey = (string) ($config['signingKey'] ?? '');
        $previousKeys = is_array($config['previousKeys'] ?? null) ? $config['previousKeys'] : [];
        if ($keyId === '' || strlen($signingKey) < 32) {
            throw new RuntimeException('客服实时服务签名密钥未正确配置。');
        }

        return [
            'keyId' => $keyId,
            'signingKey' => $signingKey,
            'previousKeys' => array_filter(
                $previousKeys,
                static fn ($key, $id): bool => is_string($id) && $id !== '' && is_string($key) && strlen($key) >= 32,
                ARRAY_FILTER_USE_BOTH
            ),
        ];
    }

    /**
     * 解码并验证JWT中的JSON对象片段。
     *
     * @return array<string, mixed>|null 片段不是合法JSON对象时返回null
     */
    private static function decodeJsonSegment(string $segment): ?array
    {
        $json = self::base64UrlDecode($segment);
        $data = $json === null ? null : json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    /**
     * 严格解码JWT的Base64 URL片段。
     */
    private static function base64UrlDecode(string $value): ?string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding), true);
        return $decoded === false ? null : $decoded;
    }
}
