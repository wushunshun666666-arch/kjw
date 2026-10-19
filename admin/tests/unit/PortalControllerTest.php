<?php

use PHPUnit\Framework\TestCase;
use common\models\system\UserIdentity;
use common\models\system\User;

/** 验证实际后台渲染、权限和请求方法，不写业务数据。 */
final class PortalControllerTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;

    /** 准备独立管理员测试身份和 GET 请求。 */
    protected function setUp(): void
    {
        Yii::$app->user->setIdentity(UserIdentity::findIdentity(User::ADMIN_ID));
        Yii::$app->request->setBodyParams([]);
        Yii::$app->request->setQueryParams([]);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->transaction = Yii::$app->db->beginTransaction();
    }

    /** 回滚通过实际 Controller 创建的测试数据。 */
    protected function tearDown(): void
    {
        if ($this->transaction?->isActive) { $this->transaction->rollBack(); }
    }

    /** 带有效 CSRF 的新增、修改与详情形成真实调用闭环。 */
    public function testGameCreateModifyAndDetail(): void
    {
        $attributes = ['code' => 'test_' . bin2hex(random_bytes(5)), 'name' => '新增测试', 'description' => '',
            'ball_count' => '3', 'ball_min' => '0', 'ball_max' => '9', 'sort_order' => '0', 'enabled' => '0'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $csrf = Yii::$app->request->getCsrfToken();
        Yii::$app->request->setBodyParams(['_csrf' => $csrf, 'AddForm' => $attributes]);
        $result = json_decode(Yii::$app->runAction('portal/game/add'), true);
        self::assertSame(1000, $result['code']);
        $entity = \common\models\lottery\Game::findOne(['code' => $attributes['code']]);
        self::assertNotNull($entity);
        $attributes['name'] = '修改测试';
        $attributes['version'] = '0';
        Yii::$app->request->setBodyParams(['_csrf' => $csrf, 'ModifyForm' => $attributes]);
        $result = json_decode(Yii::$app->runAction('portal/game/modify', ['id' => $entity->id]), true);
        self::assertSame(1000, $result['code']);
        $entity->refresh();
        self::assertSame('修改测试', $entity->name);
        self::assertSame(1, (int) $entity->version);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $html = Yii::$app->runAction('portal/game/detail', ['id' => $entity->id]);
        self::assertStringContainsString('修改测试', $html);
    }

    /** 三个后台首页可加载标准查询组件。 */
    public function testAdminIndexesRender(): void
    {
        foreach (['game', 'draw', 'notice'] as $domain) {
            $html = Yii::$app->runAction('portal/' . $domain . '/index');
            self::assertStringContainsString('data-table', $html);
        }
    }

    /** 独立新增页可渲染并携带 CSRF 保护。 */
    public function testCreateFormsRender(): void
    {
        foreach (['game', 'draw', 'notice'] as $domain) {
            $html = Yii::$app->runAction('portal/' . $domain . '/add');
            self::assertStringContainsString('_csrf', $html);
            self::assertStringContainsString('check-add', $html);
        }
    }

    /** GET 不允许触发采集写操作。 */
    public function testCollectionRejectsGet(): void
    {
        $this->expectException(\yii\web\MethodNotAllowedHttpException::class);
        Yii::$app->runAction('portal/draw/collect');
    }

    /** 无 CSRF 的写请求在采集网络调用之前被拦截。 */
    public function testCollectionRejectsMissingCsrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams([]);
        $this->expectException(\yii\web\BadRequestHttpException::class);
        Yii::$app->runAction('portal/draw/collect');
    }

    /** 采集参数经过边界模型验证，非法输入不会触发上游访问。 */
    public function testCollectionRejectsUnknownGame(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams(['_csrf' => Yii::$app->request->getCsrfToken()]);
        Yii::$app->request->setQueryParams(['CollectForm' => ['game' => 'https://example.com']]);
        $result = json_decode(Yii::$app->runAction('portal/draw/collect'), true);
        self::assertSame(2000, $result['code']);
    }

    /** 后台为七个来源展示独立采集入口与最近状态。 */
    public function testAllCollectionSourcesRender(): void
    {
        $html = Yii::$app->runAction('portal/draw/index');
        foreach (\common\models\lottery\FeedCatalog::GAMES as $definition) {
            self::assertStringContainsString('立即采集' . $definition['name'], $html);
        }
        self::assertStringContainsString('最近成功', $html);
    }

    /** 已登录但没有门户角色权限的身份被服务器拒绝。 */
    public function testUnauthorizedUserIsDenied(): void
    {
        $user = new User(['id' => 2147483646, 'name' => '测试无权限用户']);
        Yii::$app->user->setIdentity(new UserIdentity($user));
        $this->expectException(\yii\web\HttpException::class);
        $this->expectExceptionMessage('您没有权限操作该模块！');
        Yii::$app->runAction('portal/game/index');
    }

    /** 完整设置页携带上传入口、广告编辑控件和 CSRF。 */
    public function testSettingsFormRenders(): void
    {
        $html = Yii::$app->runAction('portal/settings/index');
        foreach (['登录 / 注册链接', 'portal-ad-rows', 'portal-settings-submit', 'portal%2Fsettings%2Fupload', '_csrf'] as $text) {
            self::assertStringContainsString($text, $html);
        }
    }

    /** 有效 CSRF 下通过真实 Controller 保存配置，并拒绝旧版本覆盖。 */
    public function testSettingsSaveThroughController(): void
    {
        $attributes = \common\models\lottery\Settings::current()->attributes;
        $attributes['landing'] = 'https://example.com/login-test';
        $attributes['ads_json'] = '[]';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        Yii::$app->request->setBodyParams(['_csrf' => Yii::$app->request->getCsrfToken(), 'SettingsForm' => $attributes]);
        $result = json_decode(Yii::$app->runAction('portal/settings/index'), true);
        self::assertSame(1000, $result['code'], $result['message']);
        self::assertSame($attributes['landing'], \common\models\lottery\Settings::current()->landing);
        $repeat = json_decode(Yii::$app->runAction('portal/settings/index'), true);
        self::assertSame(2000, $repeat['code']);
    }

    /** 上传动作不可通过 GET 访问。 */
    public function testSettingsUploadRejectsGet(): void
    {
        $this->expectException(\yii\web\MethodNotAllowedHttpException::class);
        Yii::$app->runAction('portal/settings/upload');
    }

    /** 上传动作保留 CSRF 检查。 */
    public function testSettingsUploadRejectsMissingCsrf(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->expectException(\yii\web\BadRequestHttpException::class);
        Yii::$app->runAction('portal/settings/upload');
    }

    /** 配置权限独立于其他门户权限。 */
    public function testSettingsRejectsUnauthorizedUser(): void
    {
        $user = new User(['id' => 2147483646, 'name' => '测试无权限用户']);
        Yii::$app->user->setIdentity(new UserIdentity($user));
        $this->expectException(\yii\web\HttpException::class);
        $this->expectExceptionMessage('您没有权限操作该模块！');
        Yii::$app->runAction('portal/settings/index');
    }
}
