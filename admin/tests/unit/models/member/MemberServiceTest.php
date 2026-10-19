<?php

/**
 * 后台会员应用服务单元测试。
 *
 * 验证后台新增会员和列表筛选流程能够正确调用领域层。
 */

namespace app\tests\unit\models\member;

use app\models\member\AddForm;
use app\models\member\AgentService;
use app\models\member\SearchForm;
use app\models\member\MemberService;
use app\models\member\TreeNodeForm;
use common\models\member\Account;
use common\models\member\Member;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\base\Security;
use yii\console\Application;
use yii\db\Connection;
use yii\web\Request;

/**
 * 后台会员服务测试用例。
 */
final class MemberServiceTest extends TestCase
{
    /**
     * 创建每个测试独享的内存数据库和Yii应用。
     */
    protected function setUp(): void
    {
        parent::setUp();

        new Application([
            'id' => 'member-service-test',
            'basePath' => dirname(__DIR__, 4),
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
     * 注销测试应用，避免用例状态互相污染。
     */
    protected function tearDown(): void
    {
        Yii::$app->errorHandler->unregister();
        Yii::$app = null;
        parent::tearDown();
    }

    /**
     * 验证后台新增会员时同步创建账户并标记后台注册方式。
     */
    public function testAddCreatesMemberAndAccount(): void
    {
        $form = $this->validAddForm();
        $this->assertTrue($form->validate());

        $service = new MemberService();
        $this->assertTrue($service->add($form));

        $member = Member::findOne(['username' => 'alice']);
        $this->assertNotNull($member);
        $this->assertNotNull(Account::findOne(['member_id' => $member->id]));
        $this->assertSame(Member::REGISTER_ADMIN, (int) $member->register_type);
    }

    /**
     * 验证会员列表可以按状态筛选。
     */
    public function testSearchFiltersByStatus(): void
    {
        $service = new MemberService();
        $form = $this->validAddForm();
        $this->assertTrue($form->validate());
        $this->assertTrue($service->add($form));

        $search = new SearchForm();
        $search->status = Member::STATUS_ACTIVE;
        $this->assertTrue($search->validate());

        /** @var Member[] $models 搜索返回的会员集合 */
        $models = $service->search($search)->getModels();
        $this->assertCount(1, $models);
        $this->assertSame('alice', $models[0]->username);
    }

    /**
     * 验证代理管理服务按需返回直属下级，并正确统计多级推广关系。
     */
    public function testPromotionTreeProvidesOverviewAndDirectChildren(): void
    {
        $memberService = new MemberService();

        $rootForm = $this->validAddForm('root');
        $this->assertTrue($rootForm->validate());
        $this->assertTrue($memberService->add($rootForm));
        $root = Member::findOne(['username' => 'root']);
        $this->assertNotNull($root);

        $childForm = $this->validAddForm('child', $root->invite_code);
        $this->assertTrue($childForm->validate());
        $this->assertTrue($memberService->add($childForm));
        $child = Member::findOne(['username' => 'child']);
        $this->assertNotNull($child);

        $grandchildForm = $this->validAddForm('grandchild', $child->invite_code);
        $this->assertTrue($grandchildForm->validate());
        $this->assertTrue($memberService->add($grandchildForm));

        // 没有上级也没有下级的独立会员不能作为代理树根节点显示。
        $standaloneForm = $this->validAddForm('standalone');
        $this->assertTrue($standaloneForm->validate());
        $this->assertTrue($memberService->add($standaloneForm));

        $agentService = new AgentService();
        $overview = $agentService->getPromotionOverview();
        $rootNodes = $agentService->getChildren(null);
        $childNodes = $agentService->getChildren((int) $root->id);
        $grandchildNodes = $agentService->getChildren((int) $child->id);

        $this->assertSame(4, $overview['memberCount']);
        $this->assertSame(2, $overview['rootMemberCount']);
        $this->assertSame(2, $overview['agentMemberCount']);
        $this->assertCount(1, $rootNodes);
        $this->assertSame((int) $root->id, $rootNodes[0]['id']);
        $this->assertTrue($rootNodes[0]['hasChildren']);
        $this->assertSame(1, $rootNodes[0]['directChildCount']);
        $this->assertSame(2, $rootNodes[0]['teamMemberCount']);
        $this->assertArrayHasKey('availableAmount', $rootNodes[0]);
        $this->assertFalse($rootNodes[0]['isFrozen']);
        $this->assertCount(1, $childNodes);
        $this->assertSame((int) $child->id, $childNodes[0]['id']);
        $this->assertTrue($childNodes[0]['hasChildren']);
        $this->assertSame(1, $childNodes[0]['directChildCount']);
        $this->assertSame(1, $childNodes[0]['teamMemberCount']);
        $this->assertCount(1, $grandchildNodes);
        $this->assertFalse($grandchildNodes[0]['hasChildren']);
        $this->assertSame(0, $grandchildNodes[0]['directChildCount']);
        $this->assertSame(0, $grandchildNodes[0]['teamMemberCount']);
    }

    /**
     * 验证代理树根节点和下级节点查询参数会被统一规范化。
     */
    public function testTreeNodeFormNormalizesRootAndMemberNode(): void
    {
        $rootForm = new TreeNodeForm();
        $this->assertSame('', $rootForm->formName());
        $this->assertTrue($rootForm->validate());
        $this->assertNull($rootForm->getParentId());

        $request = new Request();
        $request->setQueryParams(['node' => '25']);
        Yii::$app->set('request', $request);

        $childForm = new TreeNodeForm();
        $this->assertTrue($childForm->loadGet());
        $this->assertTrue($childForm->validate());
        $this->assertSame(25, $childForm->getParentId());

        $invalidForm = new TreeNodeForm();
        $invalidForm->node = 'not-a-member-id';
        $this->assertFalse($invalidForm->validate());
    }

    /**
     * 构造合法的后台新增会员表单。
     */
    private function validAddForm(string $username = 'alice', ?string $parentInviteCode = null): AddForm
    {
        $form = new AddForm();
        $form->username = $username;
        $form->password = 'Test@1234';
        $form->passwordConfirm = 'Test@1234';
        $form->nickname = '测试' . $username;
        $form->parentInviteCode = $parentInviteCode;
        return $form;
    }
}
