<?php

namespace common\models\lottery;

use common\models\Error;

/** 最多三个只读网络请求并发；数据库入库仍按彩种加锁。 */
class ConcurrentFeedGateway extends FeedGateway
{
    protected function feedUrl(string $code): string { return FeedCatalog::url($code); }

    public function fetchAll(array $games): array
    {
        $results = [];
        foreach (array_chunk($games, 3, true) as $batch) {
            $multi = curl_multi_init();
            $handles = [];
            $bodies = [];
            try {
                foreach ($batch as $code => $game) {
                    if (!FeedCatalog::matches($game)) { continue; }
                    $bodies[$code] = '';
                    $handle = curl_init($this->feedUrl($code));
                    curl_setopt_array($handle, [CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15,
                        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => ['Accept: application/json'],
                        CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$bodies, $code): int {
                            if (strlen($bodies[$code]) + strlen($chunk) > 1024 * 1024) { return 0; }
                            $bodies[$code] .= $chunk;
                            return strlen($chunk);
                        }]);
                    $handles[$code] = $handle;
                    curl_multi_add_handle($multi, $handle);
                }
                do {
                    $status = curl_multi_exec($multi, $running);
                    if ($running && $status === CURLM_OK) {
                        if (curl_multi_select($multi, 0.5) === -1) { usleep(10000); }
                    }
                } while ($running && $status === CURLM_OK);
                foreach ($handles as $code => $handle) {
                    $results[$code] = $status !== CURLM_OK || curl_errno($handle) !== 0
                        || curl_getinfo($handle, CURLINFO_RESPONSE_CODE) !== 200
                        ? new Error('采集源暂时不可用，请稍后重试。')
                        : $this->decode($bodies[$code], (int) $games[$code]->id, $code);
                }
            } finally {
                foreach ($handles as $handle) { curl_multi_remove_handle($multi, $handle); }
                curl_multi_close($multi);
            }
        }
        return $results;
    }
}
