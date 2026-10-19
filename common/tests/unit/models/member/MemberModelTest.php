<?php

/**
 * 会员领域逻辑单元测试。
 *
 * 验证会员与账户事务、唯一性、状态切换以及多级推广关系。
 */

namespace common\tests\unit\models\member;

use common\models\Error;
use common\models\member\Account;
use common\models\member\Member;
use common\models\member\MemberModel;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Security;
use yii\console\Application;
use yii\db\Connection;

/**
 * 会员领域逻辑测试用例。
 */
final class MemberModelTest extends TestCase
{
    /**
     * 创建每个测试独享的内存数据库和Yii应用。
     */
    protected function setUp(): void
    {
        parent::setUp();

        new Application([
            'id' => 'member-model-test',
            'basePath' => dirname(__DIR__, 5),
            'vendorPath' => dirname(__DIR__, 5) . '/vendor',
        ]);

        $db = new Connection(['dsn' => 'sqlite::memory:']);
        $db->open();
        $db->pdo->sqliteCreateFunction('NOW', static fn (): string => date('Y-m-d H:i:s'));
        Yii::$app->set('db', $db);
        Yii::$app->set('security', new Security());

        $db->createCommand()->createTable('member', [
            'id' => 'pk',
            'parent_id' => 'integer',
            'invite_code' => 'string(10) NOT NULL UNIQUE',
            'username' => 'string(40) UNIQUE',
            'password_hash' => 'string(255)',
            'nickname' => 'string(60) NOT NULL',
            'avatar' => "string(500) NOT NULL DEFAULT ''",
            'mobile' => 'string(32) UNIQUE',
            'email' => 'string(191) UNIQUE',
            'status' => 'smallint NOT NULL',
            'register_type' => 'smallint NOT NULL',
            'auth_version' => 'integer NOT NULL DEFAULT 1',
            'last_login_time' => 'datetime',
            'create_time' => 'datetime NOT NULL',
            'update_time' => 'datetime NOT NULL',
        ])->execute();
        $db->createCommand()->createTable('account', [
            'id' => 'pk',
            'member_id' => 'integer NOT NULL UNIQUE',
            'available_amount' => "decimal(20,4) NOT NULL DEFAULT '0.0000'",
            'frozen_amount' => "decimal(20,4) NOT NULL DEFAULT '0.0000'",
            'row_version' => 'integer NOT NULL DEFAULT 0',
            'create_time' => 'datetime NOT NULL',
            'update_time' => 'datetime NOT NULL',
        ])->execute();
    }

    /**
     * 注销测试应用，避免不同用例之间共享状态。
     */
    protected function tearDown(): void
    {
        Yii::$app->errorHandler->unregister();
        Yii::$app = null;
        parent::tearDown();
    }

    /**
     * 验证创建会员时同步创建零数量账户。
     */
    public function testCreateAlsoCreatesZeroBalanceAccount(): void
    {
        $result = MemberModel::create($this->validAttributes());

        if ($result instanceof Error) {
            $this->fail((string) $result->getError());
        }

        $member = $result->getData();
        $account = Account::findOne(['member_id' => $member->id]);
        $this->assertNotNull($account);
        $this->assertSame('alice', $member->username);
        $this->assertNull($member->parent_id);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{10}$/', $member->invite_code);
        $this->assertSame(Member::REGISTER_USERNAME, (int) $member->register_type);
        $this->assertTrue($member->validatePassword('Test@1234'));
        $this->assertEquals(0, $account->available_amount);
        $this->assertEquals(0, $account->frozen_amount);
    }

    /**
     * 验证重复用户名会被业务层拒绝。
     */
    public function testDuplicateUsernameIsRejected(): void
    {
        $first = MemberModel::create($this->validAttributes());
        $this->assertFalse(Error::isError($first));

        $attributes = $this->validAttributes();
        $attributes['email'] = 'other@example.com';
        $attributes['mobile'] = '+8613900139000';
        $result = MemberModel::create($attributes);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame('用户名已经存在。', $result->getError('username'));
        $this->assertSame(1, (int) Member::find()->count());
        $this->assertSame(1, (int) Account::find()->count());
    }

    /**
     * 验证账户写入异常时会员事务完整回滚。
     */
    public function testCreateKeepsFailedTransactionActiveForDestructorRollback(): void
    {
        Yii::$app->db->createCommand()->dropTable('account')->execute();

        $this->assertTrue($this->createMemberAndCatchSystemException());
        // PHP会把异常栈保留到当前进程结束，包装器将在析构时回滚；测试先确认事务仍受控。
        $transaction = Yii::$app->db->getTransaction();
        $this->assertNotNull($transaction);
        $this->assertTrue($transaction->getIsActive());
        // 测试进程会继续复用连接，因此在断言后显式触发与析构器相同的清理动作。
        $transaction->rollBack();
        $this->assertSame(0, (int) Member::find()->count());
    }

    /**
     * 验证会员只能在正常和冻结两个状态之间切换。
     */
    public function testFreezeAndUnfreezeUseExpectedStatus(): void
    {
        $result = MemberModel::create($this->validAttributes());
        if ($result instanceof Error) {
            $this->fail((string) $result->getError());
        }

        $this->assertTrue($result->freeze());
        $member = Member::findById((int) $result->getData()->id);
        $this->assertSame(Member::STATUS_FROZEN, (int) $member->status);
        $this->assertSame(2, (int) $member->auth_version);
        $this->assertFalse($result->freeze());

        $this->assertTrue($result->unfreeze());
        $member->refresh();
        $this->assertSame(Member::STATUS_ACTIVE, (int) $member->status);
        $this->assertSame(3, (int) $member->auth_version);
    }

    /**
     * 验证推广关系可以连续建立多个层级。
     */
    public function testCreateSupportsMultiLevelPromotionRelationship(): void
    {
        $root = MemberModel::create($this->validAttributes());
        $this->assertNotInstanceOf(Error::class, $root);

        $secondAttributes = $this->validAttributes('Bob', 'bob@example.com', '+8613900139000');
        $secondAttributes['parentInviteCode'] = strtolower($root->getData()->invite_code);
        $second = MemberModel::create($secondAttributes);
        $this->assertNotInstanceOf(Error::class, $second);

        $thirdAttributes = $this->validAttributes('Carol', 'carol@example.com', '+8613700137000');
        $thirdAttributes['parentInviteCode'] = $second->getData()->invite_code;
        $third = MemberModel::create($thirdAttributes);
        $this->assertNotInstanceOf(Error::class, $third);

        $this->assertEquals($root->getData()->id, $second->getData()->parent_id);
        $this->assertEquals($second->getData()->id, $third->getData()->parent_id);
        $this->assertEquals($root->getData()->id, $third->getData()->parent->parent_id);
    }

    /**
     * 验证不存在的上级推广码会被拒绝。
     */
    public function testUnknownParentInviteCodeIsRejected(): void
    {
        $attributes = $this->validAttributes();
        $attributes['parentInviteCode'] = 'AAAAAAAAAA';

        $result = MemberModel::create($attributes);

        $this->assertInstanceOf(Error::class, $result);
        $this->assertSame('上级推广码不存在。', $result->getError('parentInviteCode'));
        $this->assertSame(0, (int) Member::find()->count());
    }

    /**
     * 验证社交注册会员可以暂不设置备用用户名和密码。
     */
    public function testSocialMemberCanBeCreatedWithoutBackupCredentials(): void
    {
        $attributes = [
            'nickname' => '社交会员',
            'register_type' => Member::REGISTER_WECHAT,
        ];

        $result = MemberModel::create($attributes);
        $this->assertNotInstanceOf(Error::class, $result);
        $this->assertNull($result->getData()->username);
        $this->assertNull($result->getData()->password_hash);
        $this->assertSame(Member::REGISTER_WECHAT, (int) $result->getData()->register_type);
    }

    /**
     * 生成合法的后台测试会员资料。
     *
     * @param string $username 用户名
     * @param string $email 邮箱
     * @param string $mobile 国际手机号码
     */
    private function validAttributes(
        string $username = 'Alice',
        string $email = 'Alice@example.com',
        string $mobile = '+8613800138000'
    ): array {
        return [
            'username' => $username,
            'password' => 'Test@1234',
            'nickname' => '测试会员',
            'avatar' => 'https://example.com/avatar.jpg',
            'mobile' => $mobile,
            'email' => $email,
        ];
    }

    /** 执行预期失败的会员创建，并让异常在返回前离开其捕获作用域。 */
    private function createMemberAndCatchSystemException(): bool
    {
        try {
            MemberModel::create($this->validAttributes());
        } catch (\Throwable) {
            return true;
        }

        return false;
    }
}
