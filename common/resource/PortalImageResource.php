<?php

namespace common\resource;

use common\validators\PortalImageValidator;
use yii\web\UploadedFile;

/** 不可执行的门户图片存储；上传与配置保存独立，保存引用后才对外公开。 */
final class PortalImageResource extends Resource
{
    public const MAX_SIZE = 5 * 1024 * 1024;
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /** 接收已认证的真实 HTTP 上传，保留 GIF 动画原始内容。 */
    public function upload(?UploadedFile $file): array|false
    {
        if ($file === null || $file->error !== UPLOAD_ERR_OK || !is_uploaded_file($file->tempName) || (int) self::getUserId() < 1) {
            return $this->setError('上传失败，请选择图片并检查服务器上传大小限制。');
        }
        $info = $this->inspect($file->tempName, $file->name);
        if ($info === false) { return false; }
        $key = bin2hex(random_bytes(24)) . '.' . $info['extension'];
        $path = $this->path($key);
        \yii\helpers\FileHelper::createDirectory(dirname($path), 0750, true);
        if (!$file->saveAs($path, false)) { return $this->setError('图片保存失败。'); }
        $manifest = ['owner' => (int) self::getUserId(), 'mime' => $info['mime'], 'created' => time()];
        if (file_put_contents($path . '.json', json_encode($manifest), LOCK_EX) === false) {
            unlink($path);
            return $this->setError('图片信息保存失败。');
        }
        return ['reference' => 'upload:' . $key];
    }

    /** 同时校验扩展名、真实 MIME、图片头、体积和尺寸。 */
    public function inspect(string $path, string $originalName): array|false
    {
        $size = is_file($path) ? filesize($path) : false;
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        if ($size === false || $size < 1 || $size > self::MAX_SIZE || !isset($mimes[$extension])) {
            return $this->setError('图片需为 JPG、PNG、GIF 或 WebP，大小不超过 5 MB。');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $image = @getimagesize($path);
        if ($mime !== $mimes[$extension] || $image === false || ($image['mime'] ?? '') !== $mime
            || $image[0] < 1 || $image[1] < 1 || $image[0] > 10000 || $image[1] > 10000 || $image[0] * $image[1] > 50000000) {
            return $this->setError('图片内容、扩展名或尺寸不正确。');
        }
        return ['extension' => $extension === 'jpeg' ? 'jpg' : $extension, 'mime' => $mime];
    }

    /** 仅允许使用已保存图片或当前管理员刚上传的文件。 */
    public function canUse(string $reference, array $previous): bool
    {
        $file = $this->resolve($reference);
        if ($file === null) { return false; }
        if (!str_starts_with($reference, 'upload:') || in_array($reference, $previous, true)) { return true; }
        $manifest = json_decode((string) @file_get_contents($file['path'] . '.json'), true);
        return (int) ($manifest['owner'] ?? 0) === (int) self::getUserId();
    }

    /** 解析白名单图片路径；调用方仍需检查引用或管理员权限。 */
    public function resolve(string $reference): ?array
    {
        if (!(new PortalImageValidator())->validate($reference)) { return null; }
        $path = str_starts_with($reference, 'upload:') ? $this->path(substr($reference, 7))
            : \Yii::getAlias('@common/../frontend/web') . $reference;
        if (!is_file($path)) { return null; }
        $info = $this->inspect($path, $path);
        return $info === false ? null : ['path' => $path, 'mime' => $info['mime']];
    }

    /** 图片保存在 Web 根目录之外，文件名完全由服务器生成。 */
    private function path(string $key): string { return \Yii::getAlias('@common/runtime/portal-images/') . $key; }
}
