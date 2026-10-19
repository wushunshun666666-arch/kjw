<?php

/**
 * 客服临时媒体清理任务。
 *
 * 删除超过生命周期且未随消息迁移到正式区的上传资源。
 */

namespace common\jobs;

use common\resource\KefuMediaResource;

/** 客服临时媒体定期清理任务。 */
final class KefuMediaCleanupJob extends Job
{
    /** 临时媒体默认保留24小时。 */
    public int $expireSeconds = 86400;

    /** 执行客服临时媒体清理。 */
    public function handle(): bool
    {
        $safeExpireSeconds = max(3600, $this->expireSeconds);
        $resource = new KefuMediaResource();
        $resource->cleanExpiredTemporaryResources(time() - $safeExpireSeconds);
        return true;
    }
}
