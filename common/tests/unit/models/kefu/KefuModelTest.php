<?php

/**
 * 客服会话领域逻辑单元测试。
 *
 * 验证领取并发、消息幂等、未读处理、转交、结束重开和媒体限制。
 */

namespace common\tests\unit\models\kefu;

use common\models\Error;
use common\models\LogicModel;
use common\models\kefu\KefuConversation;
use common\models\kefu\KefuMessage;
use common\models\kefu\KefuModel;
use common\models\system\User;
use common\models\system\UserIdentity;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Security;
use yii\console\Application;
use yii\db\Connection;

/** 客服会话领域逻辑测试用例。 */
final class KefuModelTest extends TestCase
{
    /** 创建测试专用Yii应用、数据表及基础会员和客服。 */
    protected function setUp(): void
    {
        parent::setUp();
        new Application([
            'id' => 'kefu-model-test',
            'basePath' => dirname(__DIR__, 5),
            'vendorPath' => dirname(__DIR__, 5) . '/vendor',
        ]);

        // 每个用例使用独立SQLite内存数据库，避免污染项目数据库。
        $db = new Connection(['dsn' => 'sqlite::memory:']);
        $db->open();
        $db->pdo->sqliteCreateFunction('NOW', static fn (): string => date('Y-m-d H:i:s'));
        Yii::$app->set('db', $db);
        Yii::$app->set('security', new Security());
        $this->createTables($db);
        $this->insertFixtures($db);
        $this->setOperator(101);
    }

    /** 注销测试应用并清理全局状态。 */
    protected function tearDown(): void
    {
        Yii::$app->errorHandler->unregister();
        Yii::$app = null;
        parent::tearDown();
    }

    /** 验证同一会话只能被一个客服领取。 */
    public function testOnlyOneUserCanClaimConversation(): void
    {
        $model = $this->createConversation();
        $this->assertTrue($model->claim());

        // 使用重新查询的领域对象模拟另一名客服并发领取。
        $secondModel = KefuModel::getById((int) $model->getData()->id);
        $this->assertNotNull($secondModel);
        $this->setOperator(102);
        $this->assertFalse($secondModel->claim());
        $this->assertSame(101, (int) KefuConversation::findOne($model->getData()->id)->user_id);
    }

    /** 验证双方消息、幂等编号以及未读清零。 */
    public function testMessagesAreIdempotentAndUnreadCanBeCleared(): void
    {
        $model = $this->createConversation();
        $memberMessage = $model->sendMemberMessage(1, $this->textMessage('member-1', '你好🙂'));
        $this->assertNotInstanceOf(Error::class, $memberMessage);

        $duplicate = $model->sendMemberMessage(1, $this->textMessage('member-1', '你好🙂'));
        $this->assertNotInstanceOf(Error::class, $duplicate);
        $this->assertSame($memberMessage->id, $duplicate->id);
        $this->assertSame(1, (int) KefuMessage::find()->count());

        $secondMemberMessage = $model->sendMemberMessage(1, $this->textMessage('member-2', '第二条消息'));
        $this->assertNotInstanceOf(Error::class, $secondMemberMessage);

        $this->assertTrue($model->claim());
        $this->assertTrue($model->markUserRead((int) $memberMessage->id));
        $this->assertSame(1, (int) $model->getData()->user_unread_count);
        $this->assertNotNull(KefuMessage::findOne($memberMessage->id)->read_time);
        $this->assertNull(KefuMessage::findOne($secondMemberMessage->id)->read_time);
        $this->assertTrue($model->markUserRead((int) $secondMemberMessage->id));
        $reply = $model->sendUserMessage($this->textMessage('user-1', '您好'));
        $this->assertNotInstanceOf(Error::class, $reply);
        $this->assertTrue(KefuModel::acknowledgeDelivery((int) $reply->id, 'M', 1));
        $this->assertNotNull(KefuMessage::findOne($reply->id)->delivered_time);
        $model->getData()->refresh();
        $this->assertSame(1, (int) $model->getData()->member_unread_count);
        $this->assertTrue($model->markMemberRead(1, (int) $reply->id));
        $this->assertSame(0, (int) $model->getData()->member_unread_count);
        $this->assertNotNull(KefuMessage::findOne($reply->id)->read_time);
    }

    /** 验证结束后的会员新消息会让会话重新进入等待队列。 */
    public function testEndedConversationReopensWhenMemberSendsMessage(): void
    {
        $model = $this->createConversation();
        $this->assertTrue($model->claim());
        $this->assertTrue($model->transfer(102));
        $this->setOperator(102);
        $this->assertTrue($model->end());

        $message = $model->sendMemberMessage(1, $this->textMessage('member-reopen', '还有问题'));
        $this->assertNotInstanceOf(Error::class, $message);
        $model->getData()->refresh();
        $this->assertSame(KefuConversation::STATUS_WAITING, (int) $model->getData()->status);
        $this->assertNull($model->getData()->user_id);
    }

    /** 验证可执行文件MIME不能伪装成媒体消息发送。 */
    public function testExecutableMediaIsRejected(): void
    {
        $model = $this->createConversation();
        $result = $model->sendMemberMessage(1, [
            'message_type' => KefuMessage::TYPE_IMAGE,
            'media_url' => '/uploads/test.exe',
            'media_mime' => 'application/x-msdownload',
            'media_size' => 1024,
            'client_message_id' => 'bad-media',
        ]);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame(0, (int) KefuMessage::find()->count());
    }

    /** 创建会员长期会话并返回领域对象。 */
    private function createConversation(): KefuModel
    {
        $result = KefuModel::getOrCreate(1);
        if ($result instanceof Error) {
            $this->fail((string) $result->getError());
        }
        return $result;
    }

    /** 构造合法文字消息参数。 */
    private function textMessage(string $clientMessageId, string $content): array
    {
        return [
            'message_type' => KefuMessage::TYPE_TEXT,
            'content' => $content,
            'client_message_id' => $clientMessageId,
        ];
    }

    /** 创建客服测试需要的最小数据表。 */
    private function createTables(Connection $db): void
    {
        $db->createCommand()->createTable('member', ['id' => 'pk'])->execute();
        $db->createCommand()->createTable('user', ['id' => 'pk', 'status' => 'smallint NOT NULL'])->execute();
        $db->createCommand()->createTable('user_role', [
            'id' => 'pk', 'user_id' => 'integer NOT NULL', 'role_id' => 'integer NOT NULL',
        ])->execute();
        $db->createCommand()->createTable('role_module', [
            'id' => 'pk', 'role_id' => 'integer NOT NULL', 'module_id' => 'integer NOT NULL',
        ])->execute();
        $db->createCommand()->createTable('module', [
            'id' => 'pk', 'url' => 'string(225) NOT NULL',
        ])->execute();
        $db->createCommand()->createTable('kefu_conversation', [
            'id' => 'pk', 'member_id' => 'integer NOT NULL UNIQUE', 'user_id' => 'integer',
            'status' => 'smallint NOT NULL', 'last_message_id' => 'integer', 'last_message_type' => 'smallint',
            'last_message_preview' => "string(255) NOT NULL DEFAULT ''", 'last_message_time' => 'datetime',
            'member_unread_count' => 'integer NOT NULL DEFAULT 0', 'user_unread_count' => 'integer NOT NULL DEFAULT 0',
            'create_time' => 'datetime NOT NULL', 'update_time' => 'datetime NOT NULL',
        ])->execute();
        $db->createCommand()->createTable('kefu_message', [
            'id' => 'pk', 'conversation_id' => 'integer NOT NULL', 'sender_type' => 'smallint NOT NULL',
            'sender_member_id' => 'integer', 'sender_user_id' => 'integer', 'message_type' => 'smallint NOT NULL',
            'content' => 'text', 'media_url' => 'string(500)', 'media_mime' => 'string(100)', 'media_size' => 'integer',
            'media_duration' => 'integer', 'media_width' => 'integer', 'media_height' => 'integer',
            'client_message_id' => 'string(64) NOT NULL',
            'delivered_time' => 'datetime', 'read_time' => 'datetime',
            'create_time' => 'datetime NOT NULL',
        ])->execute();
        $db->createCommand()->createIndex(
            'uk_kefu_client',
            'kefu_message',
            ['conversation_id', 'client_message_id'],
            true
        )->execute();
    }

    /** 写入一个会员和两名有效客服。 */
    private function insertFixtures(Connection $db): void
    {
        $db->createCommand()->insert('member', ['id' => 1])->execute();
        $db->createCommand()->batchInsert('user', ['id', 'status'], [[101, 1000], [102, 1000]])->execute();
        $db->createCommand()->insert('module', ['id' => 1, 'url' => 'kefu/kefu/index'])->execute();
        $db->createCommand()->insert('user_role', ['user_id' => 102, 'role_id' => 1])->execute();
        $db->createCommand()->insert('role_module', ['role_id' => 1, 'module_id' => 1])->execute();
    }

    /** 设置当前领域动作使用的后台客服身份。 */
    private function setOperator(int $userId): void
    {
        $user = new User();
        $user->id = $userId;
        LogicModel::setIdentity(new UserIdentity($user));
    }
}
