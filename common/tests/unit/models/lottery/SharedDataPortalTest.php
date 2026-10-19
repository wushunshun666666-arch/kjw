<?php

use common\models\Error;
use common\models\lottery\CollectionModel;
use common\models\lottery\Draw;
use common\models\lottery\DrawInput;
use common\models\lottery\DrawModel;
use common\models\lottery\FeedCatalog;
use common\models\lottery\FeedGateway;
use common\models\lottery\Game;
use common\models\lottery\PredictionModel;
use common\models\lottery\Settings;
use common\models\lottery\SharedDataConfig;
use common\models\lottery\SharedDataGateway;
use frontend\models\DrawHistoryForm;
use frontend\models\DrawHistoryService;
use frontend\models\GamePortalService;
use frontend\models\ReferencePortalService;
use PHPUnit\Framework\TestCase;
use yii\caching\ArrayCache;

/** 验证中心数据与分站配置隔离，以及所有入库入口在分站模式下均被阻止。 */
final class SharedDataPortalTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;
    private array $previousParams;

    /** 样本和本地外观配置只在事务内存在。 */
    protected function setUp(): void
    {
        $this->previousParams = Yii::$app->params;
        Yii::$app->params['lotteryData'] = ['mode' => 'standalone'];
        $this->transaction = Yii::$app->db->beginTransaction();
    }

    /** 不遗留测试开奖、客户配置或应用模式。 */
    protected function tearDown(): void
    {
        if ($this->transaction?->isActive) { $this->transaction->rollBack(); }
        Yii::$app->params = $this->previousParams;
    }

    /** 返回不会连接真实服务器的测试客户配置。 */
    private function client(string $id = 'alpha'): SharedDataConfig
    {
        return new SharedDataConfig(['mode' => 'client', 'endpoint' => 'https://center.example/index.php',
            'client_id' => $id, 'token' => str_repeat('q', 64)]);
    }

    /** 在中心协议中传入既有服务的真实输出，避免测试重复实现号码格式。 */
    private function gateway(SharedDataConfig $config, array $data): SharedDataGateway
    {
        return new SharedDataGateway($config, static fn (): array => ['status' => 200,
            'body' => json_encode(['version' => 1, 'code' => 200, 'data' => $data], JSON_THROW_ON_ERROR)], new ArrayCache());
    }

    /** 真实最新和历史协议均可由两个客户读取，各自本地名称不会被中心覆盖。 */
    public function testEveryGameAndHistoryUsesSharedDataWithLocalNames(): void
    {
        foreach (array_keys(FeedCatalog::GAMES) as $code) {
            $latest = $code === 'jnd28' ? (new ReferencePortalService())->open(true)
                : (new GamePortalService())->open($code, true);
            $latest['game']['code'] = $code;
            $game = Game::findOne(['code' => $code]);
            $game->name = '客户自定义' . $code;
            self::assertTrue($game->save());
            foreach (['alpha', 'beta'] as $id) {
                $config = $this->client($id);
                $gateway = $this->gateway($config, $latest);
                $result = $code === 'jnd28' ? (new ReferencePortalService($config, $gateway))->open()
                    : (new GamePortalService($config, $gateway))->open($code);
                self::assertEquals($latest['list'], $result['list']);
                self::assertFalse($result['shared_data']['stale']);
                if ($code !== 'jnd28') { self::assertSame($game->name, $result['game']['name']); }
                else { self::assertSame('shared', $result['prediction_source']); }
            }
            $form = new DrawHistoryForm(['game' => $code, 'page_size' => 20, 'page' => 2]);
            self::assertTrue($form->validate());
            $history = (new DrawHistoryService())->search($form, true);
            $config = $this->client();
            $shared = (new DrawHistoryService($config, $this->gateway($config, $history)))->search($form);
            self::assertEquals($history['list'], $shared['list']);
            self::assertSame($history['pagination'], $shared['pagination']);
        }
    }

    /** 客户关闭彩种后不访问中心，中心仍可导出同一彩种给其他客户。 */
    public function testLocalVisibilityIsIndependentOfCenterExport(): void
    {
        $game = Game::findOne(['code' => 'az10']);
        $game->enabled = Game::HIDDEN;
        self::assertTrue($game->save());
        $config = $this->client();
        $gateway = new SharedDataGateway($config, static function (): array {
            self::fail('隐藏彩种不应向中心发起请求。');
        }, new ArrayCache());
        try {
            (new GamePortalService($config, $gateway))->open('az10');
            self::fail('隐藏彩种不能公开。');
        } catch (\yii\web\NotFoundHttpException $exception) { self::assertSame(404, $exception->statusCode); }
        self::assertSame('az10', (new GamePortalService())->open('az10', true)['game']['code']);
    }

    /** 分站不因本地残留或人工数据而偏离中心结果，读取也不会复制写入开奖库。 */
    public function testClientIgnoresLocalDrawsAndDoesNotWriteDatabase(): void
    {
        $center = (new GamePortalService())->open('jsssc', true);
        $game = Game::findOne(['code' => 'jsssc']);
        $draw = DrawModel::receive(new DrawInput(['game_id' => $game->id, 'issue' => 'qa_shared_local_only',
            'draw_time' => '2099-01-01 00:00:00', 'numbers' => '9,9,9,9,9', 'source' => '事务内本地残留记录']));
        self::assertInstanceOf(Draw::class, $draw);
        $count = Draw::find()->count();
        $config = $this->client();
        $data = (new GamePortalService($config, $this->gateway($config, $center)))->open('jsssc');
        self::assertSame($center['list'], $data['list']);
        self::assertNotContains('qa_shared_local_only', array_column($data['list'], 'issue'));
        self::assertSame($count, Draw::find()->count());
    }

    /** 外观接口始终读取客户自己的广告、标题和登录地址。 */
    public function testAppearanceRemainsLocal(): void
    {
        Settings::updateAll(['website' => 'customer.example', 'title' => '客户自己的站点',
            'landing' => 'https://customer.example/login', 'ads_json' => '[]'], ['id' => 1]);
        $config = $this->client();
        $data = (new ReferencePortalService($config))->website();
        self::assertSame('客户自己的站点', $data['title']);
        self::assertSame('https://customer.example/login', $data['landing']);
        self::assertSame([], $data['ad']);
        self::assertArrayNotHasKey('token', $data);
        self::assertArrayNotHasKey('endpoint', $data);
    }

    /** 分站采集、人工接收和生成预测在任何SQL写入或第三方网络请求之前失败。 */
    public function testClientCannotCollectReceiveOrGeneratePredictions(): void
    {
        Yii::$app->params['lotteryData'] = ['mode' => 'client', 'endpoint' => 'https://center.example/index.php',
            'client_id' => 'alpha', 'token' => str_repeat('a', 64)];
        $count = Draw::find()->count();
        $gateway = new class extends FeedGateway {
            /** 任何第三方调用都会使本测试失败。 */
            public function fetch(int $gameId, string $code = 'jnd28'): array|Error
            {
                throw new RuntimeException('分站不得调用第三方采集接口。');
            }
        };
        $model = new CollectionModel($gateway);
        self::assertFalse($model->collect('jnd28'));
        self::assertCount(7, $model->collectAll()['errors']);
        self::assertInstanceOf(Error::class, DrawModel::receive(new DrawInput()));
        self::assertInstanceOf(Error::class, PredictionModel::generateNext(1));
        self::assertSame($count, Draw::find()->count());
    }
}
