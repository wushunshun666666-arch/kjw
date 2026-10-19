<?php

namespace common\realtime;

use mylibs\security\Jwt;
use mylibs\yii\config\Configure;

/** 后台站内消息WebSocket短期Token签发和校验适配器。 */
final class NoticeTokenAdapter
{
    /** 唯一允许的JWT签名算法。 */
    private const ALGORITHM = 'HS256';

    /** 签发后台站内消息服务专用的一小时短期Token。 */
    public static function issue(int $userId, string $userName): string
    {
        $issuedAt = time();
        return Jwt::encode([
            'iss' => 'zhonghepan',
            'aud' => 'admin-message',
            'platform' => 'A',
            'id' => $userId,
            'name' => $userName,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $issuedAt + 3600,
            'jti' => bin2hex(random_bytes(16)),
        ], self::getKey(), self::ALGORITHM);
    }

    /**
     * 校验固定算法、签名、用途和有效期并返回可信载荷。
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
        $signature = self::base64UrlDecode($signatureSegment);
        if ($header === null || $payload === null || $signature === null || ($header['alg'] ?? '') !== self::ALGORITHM) {
            return null;
        }
        $expected = hash_hmac('sha256', $headerSegment . '.' . $payloadSegment, self::getKey(), true);
        $now = time();
        if (
            !hash_equals($expected, $signature)
            || ($payload['iss'] ?? '') !== 'zhonghepan'
            || ($payload['aud'] ?? '') !== 'admin-message'
            || ($payload['platform'] ?? '') !== 'A'
            || !is_string($payload['jti'] ?? null)
            || (int) ($payload['id'] ?? 0) < 1
            || (int) ($payload['nbf'] ?? 0) > $now
            || (int) ($payload['exp'] ?? 0) <= $now
        ) {
            return null;
        }
        return $payload;
    }

    /** 读取当前环境的站内消息签名密钥。 */
    private static function getKey(): string
    {
        $config = (new Configure())->loadCommonConfig('message-center');
        return (string) $config['key'];
    }

    /**
     * 解码JWT中的JSON对象片段。
     *
     * @return array<string, mixed>|null 非法JSON对象返回null
     */
    private static function decodeJsonSegment(string $segment): ?array
    {
        $json = self::base64UrlDecode($segment);
        $data = $json === null ? null : json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    /** 严格解码JWT的Base64 URL片段。 */
    private static function base64UrlDecode(string $value): ?string
    {
        $padding = (4 - strlen($value) % 4) % 4;
        $decoded = base64_decode(strtr($value, '-_', '+/') . str_repeat('=', $padding), true);
        return $decoded === false ? null : $decoded;
    }
}
