<?php

namespace common\validators;

/** 校验广告数组的结构、图片标识、跳转地址与发布状态。 */
final class PortalAdsValidator extends \yii\validators\Validator
{
    /** 最多二十条广告，保持管理员排列的顺序。 */
    protected function validateValue($value): ?array
    {
        if (!is_string($value)) { return ['广告列表格式错误。', []]; }
        $ads = json_decode($value, true);
        if (!str_starts_with(ltrim($value), '[') || !is_array($ads) || !array_is_list($ads) || count($ads) > 20) { return ['广告列表必须为数组，最多 20 条。', []]; }
        $url = new \yii\validators\UrlValidator(['validSchemes' => ['http', 'https'], 'enableIDN' => true]);
        $image = new PortalImageValidator();
        foreach ($ads as $index => $ad) {
            if (!is_array($ad) || array_diff(array_keys($ad), ['cover', 'link', 'enabled']) !== []
                || !is_string($ad['cover'] ?? null) || !$image->validate($ad['cover'])
                || !is_string($ad['link'] ?? null) || strlen($ad['link']) > 2048 || !$url->validate($ad['link'])
                || !in_array($ad['enabled'] ?? null, [0, 1], true)) {
                return ['第 {number} 条广告需上传图片、填写完整的 http/https 链接并选择发布状态。', ['number' => $index + 1]];
            }
        }
        return null;
    }
}
