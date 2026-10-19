<?php

namespace frontend\models;

/** 只读参考站公开公告；预测和开奖不经过此网关。 */
class ReferenceGateway
{
    /** 固定目标、限制响应体与等待时间，不接受访问者指定的 URL。 */
    public function fetch(string $resource, array $data = []): array
    {
        if (!in_array($resource, ['notice/list', 'notice/detail'], true)) {
            throw new \InvalidArgumentException('Unsupported reference resource.');
        }
        $key = ['portal-reference-v2', $resource, $data];
        $cached = \Yii::$app->cache->get($key);
        if (is_array($cached)) { return $cached; }
        $body = '';
        $handle = curl_init('https://pc28p.com/index/' . $resource);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Origin: https://jnd68.com'],
            CURLOPT_REFERER => 'https://jnd68.com/',
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > 2 * 1024 * 1024) { return 0; }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $json = $ok !== false && $status === 200 ? json_decode($body, true) : null;
        $valid = is_array($json) && ($json['code'] ?? 0) === 200 && is_array($json['data'] ?? null);
        $staleKey = ['last-success', $key];
        $stale = \Yii::$app->cache->get($staleKey);
        $result = $valid ? $json['data'] : (is_array($stale) ? $stale : []);
        if ($valid) { \Yii::$app->cache->set($staleKey, $result, 3600); }
        \Yii::$app->cache->set($key, $result, $valid ? 120 : 15);
        return $result;
    }
}
