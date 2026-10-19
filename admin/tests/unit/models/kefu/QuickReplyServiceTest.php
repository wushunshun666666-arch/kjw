<?php

/**
 * 客服快捷回复应用服务单元测试。
 *
 * 验证公共短语新增、修改以及启停状态切换。
 */

namespace app\tests\unit\models\kefu;

use app\models\kefu\QuickReplyForm;
use app\models\kefu\QuickReplyService;
use common\models\kefu\KefuQuickReply;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;
use yii\db\Connection;

/** 客服快捷回复服务测试用例。 */
final class QuickReplyServiceTest extends TestCase
{
    /** 创建测试应用和快捷回复内存表。 */
    protected function setUp(): void
    {
        parent::setUp();
        new Application([
            'id' => 'kefu-quick-reply-test',
            'basePath' => dirname(__DIR__, 4),
            'vendorPath' => dirname(__DIR__, 5) . '/vendor',
        ]);
        // 测试使用独立SQLite连接，不写入项目数据库。
        $db = new Connection(['dsn' => 'sqlite::memory:']);
        $db->open();
        $db->pdo->sqliteCreateFunction('NOW', static fn (): string => date('Y-m-d H:i:s'));
        Yii::$app->set('db', $db);
        $db->createCommand()->createTable('kefu_quick_reply', [
            'id' => 'pk',
            'title' => 'string(60) NOT NULL',
            'content' => 'text NOT NULL',
            'orderby' => 'integer NOT NULL DEFAULT 0',
            'status' => 'smallint NOT NULL',
            'create_time' => 'datetime NOT NULL',
            'update_time' => 'datetime NOT NULL',
        ])->execute();
    }

    /** 注销测试应用并清理全局状态。 */
    protected function tearDown(): void
    {
        Yii::$app->errorHandler->unregister();
        Yii::$app = null;
        parent::tearDown();
    }

    /** 验证公共快捷回复完整生命周期。 */
    public function testQuickReplyLifecycle(): void
    {
        $form = new QuickReplyForm();
        $form->title = '欢迎语';
        $form->content = '您好，请问有什么可以帮助您？🙂';
        $form->orderby = 10;
        $this->assertTrue($form->validate());

        $service = new QuickReplyService();
        $this->assertTrue($service->add($form));
        $model = KefuQuickReply::findOne(['title' => '欢迎语']);
        $this->assertNotNull($model);
        $this->assertTrue($service->disable((int) $model->id));
        $model->refresh();
        $this->assertSame(KefuQuickReply::STATUS_DISABLED, (int) $model->status);
        $this->assertTrue($service->enable((int) $model->id));
    }
}
