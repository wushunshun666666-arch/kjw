<?php

use common\models\lottery\Draw;
use common\models\lottery\DrawInput;
use common\models\lottery\DrawModel;
use common\models\lottery\Game;
use common\models\lottery\Notice;
use frontend\models\ReferenceGateway;
use frontend\models\ReferencePortalService;
use PHPUnit\Framework\TestCase;

/** 前端只读数据边界，全部写入在事务内回滚，开奖查询不得调用第三方。 */
final class ReferencePortalTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;

    /** 隔离测试数据。 */
    protected function setUp(): void { $this->transaction = Yii::$app->db->beginTransaction(); }

    /** 保留现有真实数据。 */
    protected function tearDown(): void { $this->transaction?->rollBack(); }

    /** 不访问网络的公开接口桩。 */
    private function service(array $responses): ReferencePortalService
    {
        return new ReferencePortalService(new class($responses) extends ReferenceGateway {
            public function __construct(private array $responses) {}
            public function fetch(string $resource, array $data = []): array { return $this->responses[$resource] ?? []; }
        });
    }

    /** 创建事务内测试开奖，使断言不依赖实时期号。 */
    private function draw(): Draw
    {
        $game = Game::findOne(['code' => 'jnd28']);
        self::assertNotNull($game);
        $game->enabled = Game::VISIBLE;
        self::assertTrue($game->save(false));
        $draw = DrawModel::receive(new DrawInput(['game_id' => $game->id, 'issue' => '999000000001',
            'draw_time' => '2099-01-01 01:00:00', 'numbers' => '1,2,3', 'source' => '事务内测试']));
        self::assertInstanceOf(Draw::class, $draw);
        return $draw;
    }

    /** 只从本地预测表读取预测，命中按本站号码计算。 */
    public function testPredictionCannotOverrideDrawAndMustMatchIssue(): void
    {
        $draw = $this->draw();
        foreach ([$draw->issue => ['小', '双'], '999000000002' => ['大', '单']] as $issue => $values) {
            $prediction = new \common\models\lottery\Prediction(['game_id' => $draw->game_id, 'issue' => (string) $issue,
                'source_issue' => (string) ((int) $issue - 1), 'algorithm' => 'test-local', 'pdx' => $values[0], 'pds' => $values[1],
                'sample_count' => 20, 'basis_json' => '[]', 'basis_hash' => hash('sha256', '[]'), 'scores_json' => '{}',
                'target_time' => '2099-01-01 01:03:30', 'create_time' => '2098-12-31 23:59:00']);
            self::assertTrue($prediction->save());
        }
        $service = new ReferencePortalService(new class extends ReferenceGateway {
            public function fetch(string $resource, array $data = []): array { throw new RuntimeException('开奖页不得访问第三方接口'); }
        });
        $data = $service->open();
        self::assertSame([1, 2, 3], $data['list'][0]['code_arr']);
        self::assertSame('1 + 2 + 3 = 6', $data['list'][0]['result']);
        self::assertSame(1, $data['list'][0]['prdx']);
        self::assertSame(1, $data['list'][0]['prds']);
        self::assertTrue($data['prediction_available']);
        self::assertSame('大', $data['prediction']['pdx']);
        self::assertSame('local', $data['prediction_source']);
        self::assertSame('test-local', $data['prediction_algorithm']);
    }

    /** 预测缺失或结构异常不伪造命中/失误，并保留真实开奖。 */
    public function testMissingOrMalformedPredictionsRemainUnavailable(): void
    {
        $this->draw();
        $data = $this->service(['website/open' => ['list' => 'invalid', 'prediction' => 'invalid']])->open();
        self::assertSame(6, $data['list'][0]['sum']);
        self::assertFalse($data['list'][0]['prediction_available']);
        self::assertNull($data['list'][0]['prdx']);
        self::assertNull($data['list'][0]['prds']);
        self::assertFalse($data['prediction_available']);
    }

    /** 隐藏彩种不会从参考站回填号码。 */
    public function testHiddenGameHasNoPublicDraws(): void
    {
        $game = Game::findOne(['code' => 'jnd28']);
        self::assertNotNull($game);
        $game->enabled = Game::HIDDEN;
        self::assertTrue($game->save(false));
        $data = $this->service(['website/open' => ['list' => [['period' => '1', 'code_arr' => [1,2,3]]]]])->open();
        self::assertSame([], $data['list']);
        self::assertSame('-', $data['period']['cperiod']);
        self::assertSame(0, $data['period']['open_time']);
    }

    /** 外部公告净化 HTML，禁止脚本和危险链接。 */
    public function testReferenceNoticeHtmlIsSanitized(): void
    {
        $data = $this->service(['notice/detail' => ['id' => 8, 'title' => '公告', 'created' => '',
            'content' => '<p>正文</p><script>alert(1)</script><a href="javascript:alert(1)">链接</a>']])->notice('reference-8');
        self::assertStringContainsString('正文', $data['content']);
        self::assertStringNotContainsString('<script', $data['content']);
        self::assertStringNotContainsString('javascript:', $data['content']);
    }

    /** 第三方网关明确拒绝旧预测端点。 */
    public function testRemotePredictionEndpointIsRemoved(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new ReferenceGateway())->fetch('website/open');
    }
}
