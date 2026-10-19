<?php

use common\models\lottery\Draw;
use common\models\lottery\DrawInput;
use common\models\lottery\DrawModel;
use common\models\lottery\Game;
use common\models\lottery\Notice;
use common\models\lottery\NoticeModel;
use frontend\models\ReferencePortalService;
use PHPUnit\Framework\TestCase;
use yii\web\NotFoundHttpException;

/** 前端只读数据边界，全部写入在事务内回滚，开奖查询不得调用第三方。 */
final class ReferencePortalTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;
    private array $previousParams;
    private string|false $previousReferenceNotices;

    /** 隔离测试数据，并验证旧参考公告开关无法恢复外部内容。 */
    protected function setUp(): void
    {
        $this->previousParams = Yii::$app->params;
        Yii::$app->params['lotteryData'] = ['mode' => 'standalone'];
        $this->previousReferenceNotices = getenv('LOTTERY_REFERENCE_NOTICES');
        putenv('LOTTERY_REFERENCE_NOTICES=1');
        $this->transaction = Yii::$app->db->beginTransaction();
    }

    /** 保留现有真实数据和部署配置。 */
    protected function tearDown(): void
    {
        $this->transaction?->rollBack();
        Yii::$app->params = $this->previousParams;
        putenv($this->previousReferenceNotices === false ? 'LOTTERY_REFERENCE_NOTICES'
            : 'LOTTERY_REFERENCE_NOTICES=' . $this->previousReferenceNotices);
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
        $data = (new ReferencePortalService())->open();
        self::assertSame([1, 2, 3], $data['list'][0]['code_arr']);
        self::assertSame('1 + 2 + 3 = 6', $data['list'][0]['result']);
        self::assertSame(1, $data['list'][0]['prdx']);
        self::assertSame(1, $data['list'][0]['prds']);
        self::assertTrue($data['prediction_available']);
        self::assertSame('大', $data['prediction']['pdx']);
        self::assertSame('local', $data['prediction_source']);
        self::assertSame('test-local', $data['prediction_algorithm']);
    }

    /** 预测缺失不伪造命中/失误，并保留真实开奖。 */
    public function testMissingPredictionsRemainUnavailable(): void
    {
        $this->draw();
        $data = (new ReferencePortalService())->open();
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
        $data = (new ReferencePortalService())->open();
        self::assertSame([], $data['list']);
        self::assertSame('-', $data['period']['cperiod']);
        self::assertSame(0, $data['period']['open_time']);
    }

    /** 空数据库不补入参考公告，即使部署仍保留旧开关。 */
    public function testEmptyNoticeListDoesNotUseReferenceContent(): void
    {
        Notice::deleteAll();
        self::assertSame([], (new ReferencePortalService())->notices());
    }

    /** 未发布公告不出现在目录中，知道详情地址也不能读取。 */
    public function testUnpublishedNoticeIsNotPublic(): void
    {
        Notice::updateAll(['enabled' => Notice::HIDDEN]);
        $notice = $this->createNotice('未发布公告', '不可公开的正文', Notice::HIDDEN);
        $service = new ReferencePortalService();
        self::assertSame([], $service->notices());
        $this->expectException(NotFoundHttpException::class);
        $service->notice('local-' . $notice->id);
    }

    /** 已发布公告按最新在前排列，详情按纯文本转义而不执行 HTML。 */
    public function testPublishedLocalNoticesKeepOrderAndEscapeContent(): void
    {
        Notice::updateAll(['enabled' => Notice::HIDDEN]);
        $older = $this->createNotice('较早公告', '第一条正文', Notice::VISIBLE);
        $newer = $this->createNotice('较新公告', "<script>alert(1)</script>\n第二行 & 正文", Notice::VISIBLE);
        $this->createNotice('隐藏公告', '不可公开', Notice::HIDDEN);
        $service = new ReferencePortalService();
        self::assertSame([
            ['id' => 'local-' . $newer->id, 'title' => $newer->title, 'created' => $newer->create_time],
            ['id' => 'local-' . $older->id, 'title' => $older->title, 'created' => $older->create_time],
        ], $service->notices());
        self::assertSame([
            'id' => 'local-' . $newer->id,
            'title' => $newer->title,
            'created' => $newer->create_time,
            'content' => "&lt;script&gt;alert(1)&lt;/script&gt;<br />\n第二行 &amp; 正文",
        ], $service->notice('local-' . $newer->id));
    }

    /** 旧参考公告详情始终返回404，不受遗留环境开关影响。 */
    public function testReferenceNoticeIdentifierIsNotPublic(): void
    {
        $this->expectException(NotFoundHttpException::class);
        (new ReferencePortalService())->notice('reference-8');
    }

    /** 使用真实后台领域动作创建公告，所有写入随测试事务回滚。 */
    private function createNotice(string $title, string $content, int $enabled): Notice
    {
        $model = NoticeModel::create(['title' => $title, 'content' => $content, 'enabled' => $enabled]);
        self::assertInstanceOf(NoticeModel::class, $model);
        $notice = $model->getData();
        self::assertTrue($notice->refresh());
        return $notice;
    }
}
