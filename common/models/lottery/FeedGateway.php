<?php

namespace common\models\lottery;

use common\models\Error;

/** 多彩种采集适配器，固定来源并限制响应体与连接时间。 */
class FeedGateway
{
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

    /** 校验统一历史接口；draw_status及其他展示字段不参与入库或冲突判断。
     * @return array<int, DrawInput>|Error 按时间升序排列的标准记录
     */
    public function decode(string $body, int $gameId, string $code = 'jnd28'): array|Error
    {
        $definition = FeedCatalog::get($code);
        if ($definition === null) { return new Error('未配置该彩种的采集地址。'); }
        if (trim($body) === '') { return new Error('采集接口返回空内容，等待上游恢复。'); }
        $payload = json_decode($body, true, 32);
        if (!is_array($payload) || ($payload['code'] ?? null) !== 1000
            || !is_array($payload['data'] ?? null)
            || ($payload['data']['lotCode'] ?? null) !== $definition['query']
            || !is_array($payload['data']['list'] ?? null) || !array_is_list($payload['data']['list'])
            || count($payload['data']['list']) > 200) {
            return new Error('采集源响应格式或彩种代码不匹配。');
        }
        if ($payload['data']['list'] === []) { return new Error('采集接口暂未返回开奖记录。'); }
        $rows = [];
        foreach ($payload['data']['list'] as $row) {
            if (!is_array($row) || !is_string($row['issue'] ?? null)
                || !is_string($row['draw_code'] ?? null) || !is_string($row['draw_time'] ?? null)) {
                return new Error('采集源记录字段不完整。');
            }
            $form = new DrawInput([
                'game_id' => $gameId, 'issue' => $row['issue'], 'numbers' => $row['draw_code'],
                'draw_time' => $row['draw_time'], 'source' => '69.165.75.20:86/' . $definition['query'],
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
