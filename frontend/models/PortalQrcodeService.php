<?php

namespace frontend\models;

use common\models\lottery\Settings;
use common\resource\PortalImageResource;

/** 根据后台登录地址生成带站点图标的二维码，保留参考站的中心标识样式。 */
final class PortalQrcodeService
{
    /** 使用高纠错等级和小面积图标，保持二维码可扫描。 */
    public function png(): string
    {
        $settings = Settings::current();
        $library = dirname(__DIR__, 2) . '/vendor/phpqrcode/phpqrcode.php';
        require_once $library;
        // 老编码器的 RS 分块以正数除法结果作数组下标；PHP 8.4 提示隐式截断。
        // 仅兼容这一条已知弃用提示，其余错误仍交给框架处理，不修改供应商包。
        $previous = null;
        $previous = set_error_handler(static function ($severity, $message, $file, $line) use (&$previous, $library): bool {
            if ($severity === E_DEPRECATED && realpath($file) === realpath($library)
                && str_starts_with($message, 'Implicit conversion from float ')) { return true; }
            return $previous !== null ? $previous($severity, $message, $file, $line) : false;
        });
        $withLogo = strlen($settings->landing) <= 900;
        try { $matrix = \QRcode::text($settings->landing, false, $withLogo ? QR_ECLEVEL_H : QR_ECLEVEL_L); }
        finally { restore_error_handler(); }
        $modules = count($matrix);
        $scale = min(8, (int) floor(1024 / ($modules + 8)));
        $qr = imagecreatetruecolor(($modules + 8) * $scale, ($modules + 8) * $scale);
        $white = imagecolorallocate($qr, 255, 255, 255);
        $black = imagecolorallocate($qr, 0, 0, 0);
        imagefill($qr, 0, 0, $white);
        foreach ($matrix as $y => $row) {
            for ($x = 0; $x < $modules; $x++) {
                if ($row[$x] === '1') { imagefilledrectangle($qr, ($x + 4) * $scale, ($y + 4) * $scale, ($x + 5) * $scale - 1, ($y + 5) * $scale - 1, $black); }
            }
        }
        $this->addLogo($qr, $settings->icon, $withLogo);
        ob_start();
        imagepng($qr);
        return (string) ob_get_clean();
    }

    /** 图标覆盖面积受限，长地址以无图标二维码优先保证可识别。 */
    private function addLogo(\GdImage $qr, string $reference, bool $enabled): void
    {
        if (!$enabled) { return; }
        $resource = (new PortalImageResource())->resolve($reference);
        if ($resource === null) { return; }
        $size = getimagesize($resource['path']);
        // 超大原图不在公开请求中解码，避免内存峰值；二维码本身仍可使用。
        if ($size === false || $size[0] * $size[1] > 4000000) { return; }
        $logo = @imagecreatefromstring((string) file_get_contents($resource['path']));
        if ($logo === false) { return; }
        $width = imagesx($qr);
        $logoWidth = (int) round($width * 0.14);
        $logoHeight = (int) round($logoWidth * imagesy($logo) / imagesx($logo));
        if ($logoHeight > $logoWidth) { $logoHeight = $logoWidth; $logoWidth = (int) round($logoHeight * imagesx($logo) / imagesy($logo)); }
        $left = (int) floor(($width - $logoWidth) / 2);
        $top = (int) floor((imagesy($qr) - $logoHeight) / 2);
        imagefilledrectangle($qr, $left - 2, $top - 2, $left + $logoWidth + 2, $top + $logoHeight + 2, imagecolorallocate($qr, 255, 255, 255));
        imagecopyresampled($qr, $logo, $left, $top, 0, 0, $logoWidth, $logoHeight, imagesx($logo), imagesy($logo));
    }
}
