<?php

/**
 * 客服媒体资源。
 *
 * 负责图片、语音和视频的临时上传、安全分析、正式迁移和受控路径解析。
 */

namespace common\resource;

use common\models\kefu\KefuMessage;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/** 客服聊天媒体资源生命周期对象。 */
final class KefuMediaResource extends Resource
{
    /** 图片最大字节数。 */
    private const IMAGE_MAX_SIZE = 10 * 1024 * 1024;

    /** 语音最大字节数。 */
    private const AUDIO_MAX_SIZE = 20 * 1024 * 1024;

    /** 视频最大字节数。 */
    private const VIDEO_MAX_SIZE = 100 * 1024 * 1024;

    /** 语音最大时长秒数。 */
    private const AUDIO_MAX_DURATION = 300;

    /** 视频最大时长秒数。 */
    private const VIDEO_MAX_DURATION = 600;

    /** 临时资源目录名。 */
    private const TEMPORARY_DIRECTORY = 'temporary';

    /** 正式资源目录名。 */
    private const PERMANENT_DIRECTORY = 'permanent';

    /**
     * 校验上传内容并保存为临时资源。
     *
     * @return array|false 成功返回安全元数据，失败返回false并记录错误
     */
    public function storeTemporary(UploadedFile $file, int $messageType): array|false
    {
        $ownerUserId = (int) self::getUserId();
        if ($ownerUserId < 1 || $file->error !== UPLOAD_ERR_OK || !is_uploaded_file($file->tempName)) {
            return $this->fail('媒体文件上传失败。');
        }

        $detectedMime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->tempName);
        if (!is_string($detectedMime)) {
            return $this->fail('无法识别媒体文件类型。');
        }

        $definition = $this->getDefinition($messageType, $detectedMime);
        if ($definition === null) {
            return $this->fail('媒体文件格式不允许。');
        }
        if ($file->size < 1 || $file->size > $definition['maxSize']) {
            return $this->fail('媒体文件大小超过限制。');
        }

        $metadata = $this->analyze($file->tempName, $messageType, $definition['maxDuration']);
        if ($metadata === false) {
            return false;
        }

        // 资源标识与物理目录无关，并使用服务端随机文件名。
        $key = date('Y/m') . '/' . bin2hex(random_bytes(24)) . '.' . $definition['extension'];
        $temporaryPath = $this->getPath(self::TEMPORARY_DIRECTORY, $key);
        FileHelper::createDirectory(dirname($temporaryPath), 0750, true);
        if (!$file->saveAs($temporaryPath, false)) {
            return $this->fail('媒体文件保存失败。');
        }

        $manifest = array_merge($metadata, [
            'ownerUserId' => $ownerUserId,
            'messageType' => $messageType,
            'mime' => $detectedMime,
            'size' => (int) $file->size,
            'createTime' => time(),
        ]);
        if (file_put_contents($this->getManifestPath($temporaryPath), json_encode($manifest)) === false) {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
            return $this->fail('媒体资源信息保存失败。');
        }

        return array_merge($metadata, [
            'key' => $key,
            'mime' => $detectedMime,
            'size' => (int) $file->size,
        ]);
    }

    /**
     * 根据临时资源构造领域消息属性，禁止客户端伪造MIME、大小和时长。
     *
     * @param array $attributes 已验证的发送表单安全字段
     * @return array|false 领域消息属性或失败结果
     */
    public function buildMessageAttributes(array $attributes): array|false
    {
        $ownerUserId = (int) self::getUserId();
        $messageType = (int) ($attributes['messageType'] ?? 0);
        $messageAttributes = [
            'message_type' => $messageType,
            'content' => $attributes['content'] ?? null,
            'client_message_id' => $attributes['clientMessageId'] ?? null,
        ];
        if ($messageType === KefuMessage::TYPE_TEXT) {
            return $messageAttributes;
        }

        $key = trim((string) ($attributes['mediaKey'] ?? ''));
        $manifest = $this->readManifest($key);
        if (
            $manifest === false
            || (int) ($manifest['ownerUserId'] ?? 0) !== $ownerUserId
            || (int) ($manifest['messageType'] ?? 0) !== $messageType
        ) {
            return $this->fail('媒体资源不存在、已失效或不属于当前客服。');
        }

        return array_merge($messageAttributes, [
            'media_url' => $key,
            'media_mime' => (string) $manifest['mime'],
            'media_size' => (int) $manifest['size'],
            'media_duration' => $manifest['duration'],
            'media_width' => $manifest['width'],
            'media_height' => $manifest['height'],
        ]);
    }

    /** 在消息事务提交后将临时资源迁移到正式目录。 */
    public function migrate(string $key): bool
    {
        $ownerUserId = (int) self::getUserId();
        $permanentPath = $this->getPath(self::PERMANENT_DIRECTORY, $key);
        if (is_file($permanentPath)) {
            if (is_file($this->getManifestPath($permanentPath))) {
                return true;
            }

            $temporaryManifestPath = $this->getManifestPath(
                $this->getPath(self::TEMPORARY_DIRECTORY, $key)
            );
            if (is_file($temporaryManifestPath)) {
                return rename($temporaryManifestPath, $this->getManifestPath($permanentPath))
                    ?: $this->setError('媒体资源清单迁移失败，请重试发送。');
            }

            return $this->setError('媒体资源清单不存在。');
        }

        $manifest = $this->readManifest($key, self::TEMPORARY_DIRECTORY);
        if ($manifest === false || (int) ($manifest['ownerUserId'] ?? 0) !== $ownerUserId) {
            return $this->setError('媒体临时资源不存在或不属于当前客服。');
        }

        $temporaryPath = $this->getPath(self::TEMPORARY_DIRECTORY, $key);
        FileHelper::createDirectory(dirname($permanentPath), 0750, true);
        if (!rename($temporaryPath, $permanentPath)) {
            return $this->setError('媒体资源迁移失败，请重试发送。');
        }
        if (!rename($this->getManifestPath($temporaryPath), $this->getManifestPath($permanentPath))) {
            // 清单迁移失败时将媒体退回临时区，使同一消息编号可以安全重试。
            rename($permanentPath, $temporaryPath);
            return $this->setError('媒体资源清单迁移失败，请重试发送。');
        }

        return true;
    }

    /** 根据资源标识解析可读取的正式或临时媒体路径。 */
    public function findPath(string $key): string|false
    {
        if (!$this->isValidKey($key)) {
            return false;
        }

        foreach ([self::PERMANENT_DIRECTORY, self::TEMPORARY_DIRECTORY] as $directory) {
            $path = $this->getPath($directory, $key);
            if (is_file($path)) {
                return $path;
            }
        }

        return false;
    }

    /** 仅解析已被聊天消息引用的媒体路径，防止预上传资源被提前读取。 */
    public function findReferencedPath(string $key): string|false
    {
        if (!KefuMessage::find()->where(['media_url' => $key])->exists()) {
            return false;
        }

        return $this->findPath($key);
    }

    /**
     * 清理指定时间之前且仍未迁移的临时媒体资源。
     *
     * @param int $expireBefore Unix过期时间戳
     * @return int 成功清理的媒体文件数量
     */
    public function cleanExpiredTemporaryResources(int $expireBefore): int
    {
        $temporaryRoot = Yii::getAlias('@common/runtime/kefu-media/' . self::TEMPORARY_DIRECTORY);
        if (!is_dir($temporaryRoot)) {
            return 0;
        }

        $cleaned = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($temporaryRoot, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || !str_ends_with($file->getFilename(), '.json')) {
                continue;
            }

            $manifestPath = $file->getPathname();
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            if (!is_array($manifest) || (int) ($manifest['createTime'] ?? 0) >= $expireBefore) {
                continue;
            }

            $mediaPath = substr($manifestPath, 0, -5);
            $key = str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                ltrim(substr($mediaPath, strlen($temporaryRoot)), DIRECTORY_SEPARATOR)
            );
            // 已被消息引用但迁移曾失败的资源必须保留，后续发送重试会再次迁移。
            if (KefuMessage::find()->where(['media_url' => $key])->exists()) {
                continue;
            }
            if (is_file($mediaPath)) {
                unlink($mediaPath);
                $cleaned++;
            }
            unlink($manifestPath);
        }

        return $cleaned;
    }

    /** 返回消息类型和真实MIME对应的安全定义。 */
    private function getDefinition(int $messageType, string $mime): ?array
    {
        $definitions = [
            KefuMessage::TYPE_IMAGE => [
                'image/jpeg' => ['jpg', self::IMAGE_MAX_SIZE],
                'image/png' => ['png', self::IMAGE_MAX_SIZE],
                'image/gif' => ['gif', self::IMAGE_MAX_SIZE],
                'image/webp' => ['webp', self::IMAGE_MAX_SIZE],
            ],
            KefuMessage::TYPE_AUDIO => [
                'audio/mpeg' => ['mp3', self::AUDIO_MAX_SIZE],
                'audio/mp4' => ['m4a', self::AUDIO_MAX_SIZE],
                'audio/ogg' => ['ogg', self::AUDIO_MAX_SIZE],
                'audio/webm' => ['webm', self::AUDIO_MAX_SIZE],
            ],
            KefuMessage::TYPE_VIDEO => [
                'video/mp4' => ['mp4', self::VIDEO_MAX_SIZE],
                'video/webm' => ['webm', self::VIDEO_MAX_SIZE],
                'video/quicktime' => ['mov', self::VIDEO_MAX_SIZE],
            ],
        ];
        if (!isset($definitions[$messageType][$mime])) {
            return null;
        }

        [$extension, $maxSize] = $definitions[$messageType][$mime];
        $maxDuration = match ($messageType) {
            KefuMessage::TYPE_AUDIO => self::AUDIO_MAX_DURATION,
            KefuMessage::TYPE_VIDEO => self::VIDEO_MAX_DURATION,
            default => null,
        };

        return ['extension' => $extension, 'maxSize' => $maxSize, 'maxDuration' => $maxDuration];
    }

    /** 解析图片尺寸或音视频时长，并执行上限校验。 */
    private function analyze(string $path, int $messageType, ?int $maxDuration): array|false
    {
        if ($messageType === KefuMessage::TYPE_IMAGE) {
            $image = getimagesize($path);
            if ($image === false) {
                return $this->fail('图片内容损坏或格式不正确。');
            }

            return ['duration' => null, 'width' => (int) $image[0], 'height' => (int) $image[1]];
        }

        $information = (new \getID3())->analyze($path);
        $duration = (int) ceil((float) ($information['playtime_seconds'] ?? 0));
        if ($duration < 1 || $maxDuration === null || $duration > $maxDuration) {
            return $this->fail('媒体文件时长无效或超过限制。');
        }

        $width = isset($information['video']['resolution_x']) ? (int) $information['video']['resolution_x'] : null;
        $height = isset($information['video']['resolution_y']) ? (int) $information['video']['resolution_y'] : null;
        return ['duration' => $duration, 'width' => $width, 'height' => $height];
    }

    /** 读取正式或临时资源的服务端清单。 */
    private function readManifest(string $key, ?string $onlyDirectory = null): array|false
    {
        if (!$this->isValidKey($key)) {
            return false;
        }

        // 未指定目录时依次查找正式区和临时区。
        $directories = $onlyDirectory === null
            ? [self::PERMANENT_DIRECTORY, self::TEMPORARY_DIRECTORY]
            : [$onlyDirectory];
        foreach ($directories as $directory) {
            $path = $this->getPath($directory, $key);
            $manifestPath = $this->getManifestPath($path);
            if (!is_file($path) || !is_file($manifestPath)) {
                continue;
            }

            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            if (is_array($manifest)) {
                return $manifest;
            }
        }

        return false;
    }

    /** 校验随机资源标识格式，阻止目录穿越。 */
    private function isValidKey(string $key): bool
    {
        return preg_match(
            '#^\d{4}/\d{2}/[a-f0-9]{48}\.(jpg|png|gif|webp|mp3|m4a|ogg|webm|mp4|mov)$#',
            $key
        ) === 1;
    }

    /** 将资源标识解析到Web根目录外的受控目录。 */
    private function getPath(string $directory, string $key): string
    {
        return Yii::getAlias('@common/runtime/kefu-media/' . $directory . '/')
            . str_replace('/', DIRECTORY_SEPARATOR, $key);
    }

    /** 返回资源清单文件路径。 */
    private function getManifestPath(string $mediaPath): string
    {
        return $mediaPath . '.json';
    }

    /** 记录资源业务错误并返回精确的false结果。 */
    private function fail(string $message): false
    {
        $this->setError($message);
        return false;
    }
}
