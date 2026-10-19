<?php

use common\models\lottery\Settings;
use common\models\lottery\SettingsModel;
use frontend\models\PortalQrcodeService;
use PHPUnit\Framework\TestCase;

/** 验证数据库登录地址驱动二维码，以及旧编码器在 PHP 8.4 下的兼容边界。 */
final class PortalQrcodeTest extends TestCase
{
    /** 测试配置只存在于回滚事务内。 */
    public function testConfiguredLinkChangesQrAndLongLinkStillRenders(): void
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $outputs = [];
            foreach (['https://example.com/login', 'https://example.com/register', 'https://example.com/?q=' . str_repeat('a', 1800)] as $url) {
                $attributes = Settings::current()->attributes;
                $attributes['landing'] = $url;
                $model = new SettingsModel();
                self::assertTrue($model->save($attributes), (string) $model->getError());
                $png = (new PortalQrcodeService())->png();
                self::assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
                $size = getimagesizefromstring($png);
                self::assertNotFalse($size);
                self::assertSame($size[0], $size[1]);
                self::assertLessThanOrEqual(1024, $size[0]);
                $outputs[] = hash('sha256', $png);
            }
            self::assertCount(3, array_unique($outputs));
            $attributes = Settings::current()->attributes;
            $attributes['landing'] = 'https://example.com/?q=' . str_repeat('中', 900);
            self::assertFalse((new SettingsModel())->save($attributes), '超长多字节链接应在保存时拒绝，不能留到二维码生成时报错。');
        } finally {
            $transaction->rollBack();
        }
    }
}
