<?php

namespace common\validators;

/** 图片字段只接受内置素材或服务端生成的上传标识。 */
final class PortalImageValidator extends \yii\validators\Validator
{
    /** 限制路径格式，阻止任意文件读取和外链注入。 */
    protected function validateValue($value): ?array
    {
        if (is_string($value) && (in_array($value, ['/assets1/banner.jpg', '/assets1/icon.jpg', '/assets1/ad.gif'], true)
            || preg_match('/^upload:[a-f0-9]{48}\.(jpg|png|gif|webp)$/D', $value))) { return null; }
        return ['请上传有效的 JPG、PNG、GIF 或 WebP 图片。', []];
    }
}
