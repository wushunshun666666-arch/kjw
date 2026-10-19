<?php

use common\models\lottery\Draw;
use common\models\lottery\DrawInput;
use common\models\lottery\DrawModel;
use common\models\lottery\FeedCatalog;
use common\models\lottery\Game;
use common\models\lottery\SharedDataConfig;
use frontend\controllers\SharedDataController;
use frontend\models\SharedDataAuth;
use PHPUnit\Framework\TestCase;
use yii\caching\ArrayCache;
use yii\mutex\FileMutex;
use yii\web\Application;
use yii\web\Request;
use yii\web\Response;

/** 中心API的请求头鉴权、只读边界、配额和分页验收，测试数据均回滚。 */
final class SharedDataApiTest extends TestCase
{
    private \yii\base\Application $previousApplication;
    private ?\yii\db\Transaction $transaction = null;
    private ArrayCache $cache;
    private FileMutex $mutex;
    private ?string $previousMethod;

    /** 用原连接创建隔离的Web请求环境，不加载或修改真实中心凭据。 */
    protected function setUp(): void
    {
        $this->previousApplication = Yii::$app;
        $this->previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->transaction = Yii::$app->db->beginTransaction();
        $this->cache = new ArrayCache();
        $this->mutex = new FileMutex(['mutexPath' => '@frontend/runtime/qa-shared-data-locks']);
        new Application([
            'id' => 'shared-data-api-tests',
            'basePath' => Yii::getAlias('@frontend'),
            'timeZone' => 'Asia/Shanghai',
            'components' => [
                'db' => $this->previousApplication->db,
                'request' => ['class' => Request::class, 'enableCookieValidation' => false,
                    'scriptFile' => Yii::getAlias('@frontend/web/index.php'), 'scriptUrl' => '/index.php'],
                'cache' => $this->cache,
            ],
        ]);
        Yii::$app->errorHandler->unregister();
    }

    /** 恢复真实应用上下文并回滚所有测试开奖及配置改动。 */
    protected function tearDown(): void
    {
        if ($this->transaction?->isActive) {
            $this->transaction->rollBack();
        }
        Yii::$app = $this->previousApplication;
        if ($this->previousMethod === null) {
            unset($_SERVER['REQUEST_METHOD']);
        } else {
            $_SERVER['REQUEST_METHOD'] = $this->previousMethod;
        }
    }

    /** 使用仅存在于本例内的假凭据，保存的配置只包含摘要。 */
    private function config(string $mode = 'center'): SharedDataConfig
    {
        return new SharedDataConfig(['mode' => $mode, 'clients' => [
            'alpha' => ['token_hash' => hash('sha256', str_repeat('a', 64)), 'enabled' => true],
            'beta' => ['token_hash' => hash('sha256', str_repeat('b', 64)), 'enabled' => true],
            'disabled' => ['token_hash' => hash('sha256', str_repeat('d', 64)), 'enabled' => false],
        ]]);
    }

    /** 经真实控制器生命周期运行请求，验证过滤器、鉴权与JSON错误协议。 */
    private function request(string $action, array $query = [], ?string $client = 'alpha', ?string $token = null,
        string $method = 'GET', ?SharedDataConfig $config = null): Response
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        Yii::$app->request->setQueryParams($query);
        Yii::$app->request->headers->removeAll();
        if ($client !== null) {
            Yii::$app->request->headers->set('X-Lottery-Client', $client);
            Yii::$app->request->headers->set('Authorization', 'Bearer ' . ($token ?? str_repeat('a', 64)));
        }
        Yii::$app->set('response', new Response());
        $auth = new SharedDataAuth($config ?? $this->config(), $this->cache, $this->mutex);
        $controller = new SharedDataController('shared-data', Yii::$app, ['sharedAuth' => $auth]);
        $response = $controller->runAction($action);
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::FORMAT_JSON, $response->format);
        self::assertSame('no-store', $response->headers->get('Cache-Control'));
        self::assertSame(1, $response->data['version']);
        self::assertSame($response->statusCode, $response->data['code']);
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        return $response;
    }

    /** 通过领域入口写入未来时间的事务内开奖，不覆盖任何既有真实数据。 */
    private function draw(string $code, string $issue, string $time = '2099-10-20 12:00:00'): Draw
    {
        $game = Game::findOne(['code' => $code]);
        self::assertNotNull($game);
        $definition = FeedCatalog::get($code);
        $numbers = $definition['type'] === 'racing' ? '2,4,9,3,7,1,10,5,8,6'
            : ($definition['count'] === 5 ? '0,8,7,1,2' : '1,2,3');
        $draw = DrawModel::receive(new DrawInput(['game_id' => $game->id, 'issue' => $issue,
            'draw_time' => $time, 'numbers' => $numbers, 'source' => '事务内共享API测试']));
        self::assertInstanceOf(Draw::class, $draw);
        return $draw;
    }

    /** 凭据缺失、未知客户、停用客户和错误密钥不泄漏状态，查询串不能代替请求头。 */
    public function testCredentialsAreRequiredBeforeQueryValidation(): void
    {
        $messages = [];
        foreach ([[null, null], ['unknown', str_repeat('a', 64)], ['disabled', str_repeat('d', 64)],
            ['alpha', str_repeat('z', 64)], ['../alpha', str_repeat('a', 64)], ['alpha', 'short']] as [$id, $token]) {
            $response = $this->request('latest', ['game' => ['invalid'], 'token' => str_repeat('a', 64)], $id, $token);
            self::assertSame(401, $response->statusCode);
            self::assertNull($response->data['data']);
            self::assertTrue($response->headers->has('WWW-Authenticate'));
            $messages[] = $response->data['msg'];
        }
        self::assertCount(1, array_unique($messages));
    }

    /** 非中心模式不暴露共享入口，具有有效凭据也不能绕过实例边界。 */
    public function testNonCenterDoesNotExposeEndpoints(): void
    {
        $response = $this->request('latest', [], 'alpha', null, 'GET', $this->config('standalone'));
        self::assertSame(404, $response->statusCode);
        self::assertNull($response->data['data']);
    }

    /** 真实Yii容器创建控制器时必须读取部署配置，不能自动注入默认单站模式。 */
    public function testContainerCreationUsesConfiguredCenterMode(): void
    {
        Yii::$app->params['lotteryData'] = ['mode' => 'center', 'clients' => $this->config()->clients()];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Yii::$app->request->setQueryParams(['game' => 'az10']);
        Yii::$app->request->headers->set('X-Lottery-Client', 'alpha');
        Yii::$app->request->headers->set('Authorization', 'Bearer ' . str_repeat('a', 64));
        Yii::$app->set('response', new Response());
        $controller = Yii::createObject(SharedDataController::class, ['shared-data', Yii::$app]);
        $response = $controller->runAction('latest');
        self::assertSame(200, $response->statusCode);
        self::assertSame('az10', $response->data['data']['game']['code']);
    }

    /** POST和OPTIONS在数据查询之前返回405，合法凭据不授予写能力。 */
    public function testOnlyGetIsAccepted(): void
    {
        foreach (['POST', 'PUT', 'DELETE', 'OPTIONS'] as $method) {
            $response = $this->request('latest', ['game' => 'invalid'], 'alpha', null, $method);
            self::assertSame(405, $response->statusCode);
            self::assertSame('GET', $response->headers->get('Allow'));
        }
    }

    /** 已认证客户的非法彩种、页码、数组和无界页长被统一拒绝。 */
    public function testInvalidInputsAreRejected(): void
    {
        foreach ([['latest', ['game' => 'unknown']], ['latest', ['game' => ['jnd28']]],
            ['history', ['page' => 0]], ['history', ['page_size' => 1000]],
            ['history', ['snapshot' => -1]], ['history', ['page' => [1]]]] as [$action, $query]) {
            self::assertSame(400, $this->request($action, $query)->statusCode);
        }
    }

    /** 所有彩种共享不受中心本站隐藏配置影响，各客户看到同一数据且没有站点秘密。 */
    public function testAllGamesAreSharedIndependentlyOfCenterVisibility(): void
    {
        foreach (array_keys(FeedCatalog::GAMES) as $code) {
            $draw = $this->draw($code, 'qa_shared_visibility');
            $game = Game::findOne(['code' => $code]);
            $game->enabled = Game::HIDDEN;
            self::assertTrue($game->save());
            $first = $this->request('latest', ['game' => $code]);
            $second = $this->request('latest', ['game' => $code], 'beta', str_repeat('b', 64));
            self::assertSame(200, $first->statusCode);
            self::assertSame(200, $second->statusCode);
            self::assertSame($code, $first->data['data']['game']['code']);
            self::assertSame($draw->issue, $first->data['data']['list'][0][$code === 'jnd28' ? 'period' : 'issue']);
            self::assertSame($first->data['data']['list'], $second->data['data']['list']);
            $encoded = json_encode($first->data, JSON_THROW_ON_ERROR);
            foreach (['token_hash', 'basis_json', 'scores_json', 'password', 'landing', '"ad"'] as $privateField) {
                self::assertStringNotContainsString($privateField, $encoded);
            }
            $history = $this->request('history', ['game' => $code, 'page_size' => 20]);
            self::assertSame(200, $history->statusCode);
            self::assertSame($draw->issue, $history->data['data']['list'][0][$code === 'jnd28' ? 'period' : 'issue']);
        }
    }

    /** 配置号码规则与固定白名单不一致时不会共享错误游戏数据。 */
    public function testMisconfiguredGameIsNotShared(): void
    {
        $game = Game::findOne(['code' => 'jsssc']);
        $game->ball_count = 3;
        self::assertTrue($game->save());
        self::assertSame(404, $this->request('latest', ['game' => 'jsssc'])->statusCode);
        self::assertSame(404, $this->request('history', ['game' => 'jsssc'])->statusCode);
    }

    /** 跨页共享使用同一快照，期间新增开奖不会造成第二页重复或错位。 */
    public function testHistorySnapshotDoesNotMoveWhenNewDrawArrives(): void
    {
        $issues = [];
        for ($index = 1; $index <= 42; $index++) {
            $issues[] = $this->draw('jsssc', 'qa_shared_history_' . $index)->issue;
        }
        $first = $this->request('history', ['game' => 'jsssc', 'page_size' => 20])->data['data'];
        $this->draw('jsssc', 'qa_shared_new', '2099-10-20 12:01:00');
        $second = $this->request('history', ['game' => 'jsssc', 'page_size' => 20, 'page' => 2,
            'snapshot' => $first['pagination']['snapshot']])->data['data'];
        self::assertSame($first['pagination']['snapshot'], $second['pagination']['snapshot']);
        self::assertSame($first['pagination']['total'], $second['pagination']['total']);
        self::assertSame(array_slice(array_reverse($issues), 20, 20), array_column($second['list'], 'issue'));
        self::assertSame([], array_intersect(array_column($first['list'], 'issue'), array_column($second['list'], 'issue')));
    }

    /** 客户配额互相独立、分钟结束恢复，超额请求返回429及Retry-After。 */
    public function testRateLimitIsPerClientAndReturnsRetryAfter(): void
    {
        $auth = new SharedDataAuth($this->config(), $this->cache, $this->mutex);
        $now = time();
        for ($index = 0; $index < SharedDataAuth::REQUESTS_PER_MINUTE; $index++) {
            self::assertSame(0, $auth->consumeQuota('alpha', $now));
        }
        self::assertGreaterThan(0, $auth->consumeQuota('alpha', $now));
        self::assertSame(0, $auth->consumeQuota('beta', $now));
        $response = $this->request('latest', ['game' => 'invalid']);
        self::assertSame(429, $response->statusCode);
        self::assertGreaterThanOrEqual(1, (int) $response->headers->get('Retry-After'));
        self::assertLessThanOrEqual(60, (int) $response->headers->get('Retry-After'));
        self::assertSame(0, $auth->consumeQuota('alpha', (intdiv($now, 60) + 1) * 60));
    }
}
