<?php

/**
 * 客服媒体安全服务单元测试。
 *
 * 验证安全媒体标识、Web根目录外寻址以及服务端图片元数据解析。
 */

namespace app\tests\unit\models\kefu;

use app\models\kefu\SendForm;
use common\models\kefu\KefuMessage;
use common\models\LogicModel;
use common\models\system\UserIdentity;
use common\resource\KefuMediaResource;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;

/** 客服媒体安全服务测试用例。 */
final class KefuMediaServiceTest extends TestCase
{
    /** @var string 测试图片绝对路径 */
    private string $imagePath;

    /** 创建测试应用和Web根目录外的1像素PNG。 */
    protected function setUp(): void
    {
        parent::setUp();
        new Application([
            'id' => 'kefu-media-test',
            'basePath' => dirname(__DIR__, 4),
            'vendorPath' => dirname(__DIR__, 5) . '/vendor',
        ]);
        Yii::setAlias('common', dirname(__DIR__, 5) . '/common');

        // 固定测试媒体标识符合生产环境目录穿越防护正则。
        $key = '2099/01/' . str_repeat('a', 48) . '.png';
        $this->imagePath = Yii::getAlias('@common/runtime/kefu-media/permanent/')
            . str_replace('/', DIRECTORY_SEPARATOR, $key);
        if (!is_dir(dirname($this->imagePath))) {
            mkdir(dirname($this->imagePath), 0750, true);
        }
        // 内容是完整的1×1像素PNG，MIME和尺寸均由服务端读取。
        file_put_contents(
            $this->imagePath,
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwC'
                . 'AAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
            )
        );
        $user = (object) ['id' => 101];
        LogicModel::setIdentity(new UserIdentity($user));
        file_put_contents($this->imagePath . '.json', json_encode([
            'ownerUserId' => 101,
            'messageType' => KefuMessage::TYPE_IMAGE,
            'mime' => 'image/png',
            'size' => filesize($this->imagePath),
            'duration' => null,
            'width' => 1,
            'height' => 1,
        ]));
    }

    /** 删除测试媒体并注销Yii应用。 */
    protected function tearDown(): void
    {
        if (is_file($this->imagePath)) {
            unlink($this->imagePath);
        }
        if (is_file($this->imagePath . '.json')) {
            unlink($this->imagePath . '.json');
        }
        Yii::$app->errorHandler->unregister();
        Yii::$app = null;
        parent::tearDown();
    }

    /** 验证客户端只能通过安全媒体标识取得服务端解析属性。 */
    public function testBuildImageMessageAttributes(): void
    {
        $form = new SendForm();
        $form->messageType = KefuMessage::TYPE_IMAGE;
        $form->mediaKey = '2099/01/' . str_repeat('a', 48) . '.png';
        $form->clientMessageId = 'image-test';
        $this->assertTrue($form->validate());

        $resource = new KefuMediaResource();
        $attributes = $resource->buildMessageAttributes($form->getSafeAttributes());
        $this->assertIsArray($attributes);
        $this->assertSame('image/png', $attributes['media_mime']);
        $this->assertSame(1, $attributes['media_width']);
        $this->assertSame(1, $attributes['media_height']);
    }

    /** 验证目录穿越媒体标识不能读取任意服务器文件。 */
    public function testRejectsPathTraversalKey(): void
    {
        $resource = new KefuMediaResource();
        $this->assertFalse($resource->findPath('../../composer.json'));
    }
}
