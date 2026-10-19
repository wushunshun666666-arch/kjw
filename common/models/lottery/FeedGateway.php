<?php

namespace common\models\lottery;

use common\models\Error;

/** 多彩种采集适配器，固定来源并限制响应体与连接时间。 */
class FeedGateway
{
    public const URL = 'http://125.77.158.146:9501/Api/getGameData.php?gamename=jnd28';

    /** 读取上游数据；只读网络失败返回可展示的业务错误。
     * @return array<int, DrawInput>|Error 已按时间升序排列的标准记录
     */
    public function fetch(int $gameId, string $code = 'jnd28'): array|Error
    {
        if (FeedCatalog::get($code) === null) { return new Error('未配置该彩种的采集地址。'); }
        $handle = curl_init(FeedCatalog::url($code));
        $body = '';
        curl_setopt_array($handle, [
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > 1024 * 1024) { return 0; }
                $body .= $chunk;
                return strlen($chunk);
            },
        ]);
        $ok = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        if ($ok === false || $status !== 200) { return new Error('采集源暂时不可用，请稍后重试。'); }
        return $this->decode($body, $gameId, $code);
    }

    /** 将上游 draw/results/open_time 映射为统一入库字段。
     * @return array<int, DrawInput>|Error 标准记录或协议错误
     */
    public function decode(string $body, int $gameId, string $code = 'jnd28'): array|Error
    {
        $definition = FeedCatalog::get($code);
        if ($definition === null) { return new Error('未配置该彩种的采集地址。'); }
        if (trim($body) === '') { return new Error('采集接口返回空内容，等待上游恢复。'); }
        $payload = json_decode($body, true, 32);
        if (!is_array($payload) || ($code === 'jnd28' && ($payload['code'] ?? null) !== $code)
            || (isset($payload['code']) && $payload['code'] !== $code)
            || !isset($payload['data']) || !is_array($payload['data']) || !array_is_list($payload['data'])
            || count($payload['data']) > 200) {
            return new Error('采集源响应格式不符合该彩种协议。');
        }
        if ($payload['data'] === []) { return new Error('采集接口暂未返回开奖记录。'); }
        [$issueField, $numbersField, $timeField] = $code === 'jnd28'
            ? ['draw', 'results', 'open_time'] : ['expect', 'opencode', 'opentime'];
        $rows = [];
        foreach ($payload['data'] as $row) {
            if (!is_array($row) || !is_string($row[$issueField] ?? null)
                || !is_string($row[$numbersField] ?? null) || !is_string($row[$timeField] ?? null)) {
                return new Error('采集源记录字段不完整。');
            }
            $form = new DrawInput([
                'game_id' => $gameId, 'issue' => $row[$issueField], 'numbers' => $row[$numbersField],
                'draw_time' => $row[$timeField], 'source' => $definition['name'] . '采集接口',
            ]);
            if (!$form->validate()) { return new Error($form->getErrors()); }
            if (!FeedCatalog::accepts($code, array_map('intval', explode(',', $form->numbers)))) {
                return new Error('采集号码数量、范围或重复情况不符合该彩种规则。');
            }
            $rows[] = $form;
        }
        usort($rows, static fn (DrawInput $a, DrawInput $b): int => strcmp($a->draw_time, $b->draw_time));
        return $rows;
    }
}
