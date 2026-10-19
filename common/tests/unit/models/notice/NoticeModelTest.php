<?php

namespace common\tests\unit\models\notice;

use common\models\Error;
use common\models\notice\Notice;
use common\models\notice\NoticeModel;
use common\models\notice\NoticeRecipient;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\console\Application;
use yii\db\Connection;

/** 后台站内消息领域逻辑测试用例。 */
final class NoticeModelTest extends TestCase
{
    /** 创建测试专用Yii应用、SQLite表和后台用户。 */
    protected function setUp(): void
    {
        parent::setUp();
        new Application([
            'id' => 'admin-message-model-test',
            'basePath' => dirname(__DIR__, 5),
            'vendorPath' => dirname(__DIR__, 5) . '/vendor',
        ]);
        $db = new Connection(['dsn' => 'sqlite::memory:']);
        $db->open();
        $db->pdo->sqliteCreateFunction('NOW', static fn (): string => date('Y-m-d H:i:s'));
        Yii::$app->set('db', $db);
        $this->createTables($db);
        $db->createCommand()->batchInsert('user', ['id', 'status'], [[101, 1000], [102, 1100]])->execute();
    }

    /** 注销测试应用并清理全局状态。 */
    protected function tearDown(): void
    {
        Yii::$app->errorHandler->unregister();
        Yii::$app = null;
        parent::tearDown();
    }

    /** 验证消息及接收记录原子创建，且重复接收人会被去重。 */
    public function testMessageAndRecipientsAreCreatedAtomically(): void
    {
        $result = NoticeModel::create($this->messageAttributes(), [101, 101]);
        $this->assertNotInstanceOf(Error::class, $result);
        $this->assertSame(1, (int) Notice::find()->count());
        $this->assertSame(1, (int) NoticeRecipient::find()->count());
    }

    /** 验证不可登录的后台用户不会产生消息或孤立接收记录。 */
    public function testUnavailableRecipientRollsBackWholeMessage(): void
    {
        $result = NoticeModel::create($this->messageAttributes(), [102]);
        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(0, (int) Notice::find()->count());
        $this->assertSame(0, (int) NoticeRecipient::find()->count());
    }

    /** 验证同一消息重复标记已读保持幂等。 */
    public function testReadActionIsIdempotent(): void
    {
        $result = NoticeModel::create($this->messageAttributes(), [101]);
        $this->assertNotInstanceOf(Error::class, $result);
        $this->assertTrue($result->markRead(101));
        $firstReadTime = NoticeRecipient::findOne(['user_id' => 101])->read_time;
        $this->assertNotNull($firstReadTime);
        $this->assertTrue($result->markRead(101));
        $this->assertSame($firstReadTime, NoticeRecipient::findOne(['user_id' => 101])->read_time);
    }

    /** 返回测试使用的合法普通通知内容。 */
    private function messageAttributes(): array
    {
        return ['type' => Notice::TYPE_NOTICE, 'title' => '测试通知', 'content' => '测试内容'];
    }

    /** 创建站内消息测试需要的最小数据表。 */
    private function createTables(Connection $db): void
    {
        $db->createCommand()->createTable('user', ['id' => 'pk', 'status' => 'smallint NOT NULL'])->execute();
        $db->createCommand()->createTable('notice', [
            'id' => 'pk', 'type' => 'smallint NOT NULL', 'title' => 'string(100) NOT NULL',
            'content' => 'string(1000) NOT NULL', 'extras' => 'text', 'create_user_id' => 'integer',
            'create_time' => 'datetime NOT NULL',
        ])->execute();
        $db->createCommand()->createTable('notice_recipient', [
            'id' => 'pk', 'message_id' => 'integer NOT NULL', 'user_id' => 'integer NOT NULL',
            'read_time' => 'datetime', 'create_time' => 'datetime NOT NULL',
        ])->execute();
        $db->createCommand()->createIndex(
            'uk_notice_recipient_message_user',
            'notice_recipient',
            ['message_id', 'user_id'],
            true
        )->execute();
    }
}
