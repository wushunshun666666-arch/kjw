<?php

use common\models\lottery\Settings;
use common\models\lottery\SettingsModel;
use common\models\lottery\Audit;
use common\resource\PortalImageResource;
use frontend\models\ReferencePortalService;
use PHPUnit\Framework\TestCase;

/** 配置发布、并发、上传格式与资源公开边界的事务隔离验收。 */
final class SettingsTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;
    private array $files = [];

    /** 每例回滚配置变更。 */
    protected function setUp(): void { $this->transaction = Yii::$app->db->beginTransaction(); }

    /** 只清理本测试创建的临时文件。 */
    protected function tearDown(): void
    {
        $this->transaction?->rollBack();
        foreach ($this->files as $file) { if (is_file($file)) { unlink($file); } }
    }

    /** 新登录地址、广告顺序及发布状态从数据库即时生成公开配置。 */
    public function testSavedSettingsDrivePublicLinksAndOrdering(): void
    {
        $attributes = Settings::current()->attributes;
        $attributes['landing'] = 'https://example.com/login?from=portal';
        $attributes['ads_json'] = json_encode([
            ['cover' => '/assets1/banner.jpg', 'link' => 'https://example.com/second', 'enabled' => 1],
            ['cover' => '/assets1/ad.gif', 'link' => 'https://example.com/hidden', 'enabled' => 0],
            ['cover' => '/assets1/icon.jpg', 'link' => 'https://example.com/first', 'enabled' => 1],
        ]);
        $before = Audit::find()->where(['entity_type' => 'settings'])->count();
        $model = new SettingsModel();
        self::assertTrue($model->save($attributes), (string) $model->getError());
        $public = (new ReferencePortalService())->website();
        self::assertSame($attributes['landing'], $public['landing']);
        self::assertSame(['https://example.com/second', 'https://example.com/first'], array_column($public['ad'], 'link'));
        self::assertArrayNotHasKey('ads_json', $public);
        self::assertArrayNotHasKey('version', $public);
        self::assertSame((int) $before + 1, (int) Audit::find()->where(['entity_type' => 'settings'])->count());
        self::assertSame((int) $attributes['version'] + 1, (int) Settings::current()->version);
        self::assertFalse((new SettingsModel())->save($attributes));
        self::assertNull((new ReferencePortalService())->image('/assets1/ad.gif'));
    }

    /** 移除所有广告是真实空列表，不能回退到旧广告。 */
    public function testRemovingAllAdsKeepsThemRemoved(): void
    {
        $attributes = Settings::current()->attributes;
        $attributes['ads_json'] = '[]';
        $model = new SettingsModel();
        self::assertTrue($model->save($attributes), (string) $model->getError());
        self::assertSame([], (new ReferencePortalService())->website()['ad']);
    }

    /** 脚本协议、伪造图片、路径穿越和缺失图片不得写入配置。 */
    public function testUnsafeOrMissingImagesAndLinksAreRejected(): void
    {
        $attributes = Settings::current()->attributes;
        foreach (['javascript:alert(1)', '//example.com', 'data:text/html,test'] as $url) {
            self::assertFalse((new SettingsModel())->save(array_replace($attributes, ['landing' => $url])));
        }
        foreach (['../../common/config/main.php', 'https://example.com/image.png', 'upload:' . str_repeat('a', 48) . '.png'] as $image) {
            self::assertFalse((new SettingsModel())->save(array_replace($attributes, ['banner' => $image])));
        }
        foreach (['{}', '[{}]', json_encode([['cover' => '/assets1/ad.gif', 'link' => 'javascript:alert(1)', 'enabled' => 1]])] as $json) {
            self::assertFalse((new SettingsModel())->save(array_replace($attributes, ['ads_json' => $json])));
        }
        self::assertSame($attributes['landing'], Settings::current()->landing);
    }

    /** 上传校验识别伪装扩展、无效图片和超限文件，GIF 保留原格式。 */
    public function testImageContentAndSizeAreValidated(): void
    {
        $resource = new PortalImageResource();
        $gif = Yii::getAlias('@frontend/web/assets1/ad.gif');
        self::assertSame('image/gif', $resource->inspect($gif, 'banner.gif')['mime']);
        self::assertFalse($resource->inspect($gif, 'banner.php'));
        self::assertFalse($resource->inspect($gif, 'banner.png'));
        $file = tempnam(sys_get_temp_dir(), 'portal-image-test-');
        $this->files[] = $file;
        file_put_contents($file, '<?php echo "invalid";');
        self::assertFalse($resource->inspect($file, 'banner.jpg'));
        file_put_contents($file, str_repeat('x', PortalImageResource::MAX_SIZE + 1));
        self::assertFalse($resource->inspect($file, 'banner.jpg'));
        self::assertNull($resource->resolve('../../common/config/main-dev.php'));
        self::assertFalse($resource->upload(null));
    }
}
