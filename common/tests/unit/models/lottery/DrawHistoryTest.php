<?php

use common\models\lottery\Draw;
use common\models\lottery\DrawInput;
use common\models\lottery\DrawModel;
use common\models\lottery\FeedCatalog;
use common\models\lottery\Game;
use common\models\lottery\Prediction;
use frontend\models\DrawHistoryForm;
use frontend\models\DrawHistoryService;
use frontend\models\GamePortalService;
use frontend\models\ReferencePortalService;
use PHPUnit\Framework\TestCase;
use yii\web\NotFoundHttpException;

/** 历史查询分页、采集并发和公开边界验收，全部测试写入由事务回滚。 */
final class DrawHistoryTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;

    /** 隔离测试样本，保留既有真实开奖记录。 */
    protected function setUp(): void
    {
        $this->transaction = Yii::$app->db->beginTransaction();
    }

    /** 测试成功或失败均不遗留开奖、预测和发布配置。 */
    protected function tearDown(): void
    {
        if ($this->transaction?->isActive) {
            $this->transaction->rollBack();
        }
    }

    /** 经领域入口写入事务内号码，未来时间使样本不受实时采集干扰。 */
    private function draw(string $code, string $issue, string $time = '2099-10-19 12:00:00'): Draw
    {
        $game = Game::findOne(['code' => $code]);
        self::assertNotNull($game);
        self::assertTrue($game->isVisible());
        $definition = FeedCatalog::get($code);
        $numbers = $definition['type'] === 'racing' ? '2,4,9,3,7,1,10,5,8,6'
            : ($definition['count'] === 5 ? '0,8,7,1,2' : '1,2,3');
        $draw = DrawModel::receive(new DrawInput([
            'game_id' => $game->id,
            'issue' => $issue,
            'draw_time' => $time,
            'numbers' => $numbers,
            'source' => '事务内历史分页测试',
        ]));
        self::assertInstanceOf(Draw::class, $draw);
        return $draw;
    }

    /** 通过与控制器一致的输入校验执行只读分页。 */
    private function history(array $parameters = []): array
    {
        $form = new DrawHistoryForm($parameters);
        self::assertTrue($form->validate(), json_encode($form->getErrors(), JSON_UNESCAPED_UNICODE));
        return (new DrawHistoryService())->search($form);
    }

    /** 同一快照下跨过原100期限制且不重复；新采集和迟到补采均不会挤动页码。 */
    public function testSnapshotRemainsStableAcrossNewAndLateDraws(): void
    {
        $issues = [];
        for ($index = 1; $index <= 125; $index++) {
            $issues[] = $this->draw('jsssc', 'qa_history_' . $index)->issue;
        }
        $issues = array_reverse($issues);
        $first = $this->history(['game' => 'jsssc', 'page_size' => 20]);
        $snapshot = $first['pagination']['snapshot'];
        self::assertSame(array_slice($issues, 0, 20), array_column($first['list'], 'issue'));

        $this->draw('jsssc', 'qa_history_new', '2099-10-19 12:01:00');
        $this->draw('jsssc', 'qa_history_late', '2099-10-19 11:59:00');
        $collected = array_column($first['list'], 'issue');
        for ($page = 2; $page <= 6; $page++) {
            $data = $this->history(['game' => 'jsssc', 'page_size' => 20, 'page' => $page, 'snapshot' => $snapshot]);
            self::assertSame($snapshot, $data['pagination']['snapshot']);
            self::assertSame($first['pagination']['total'], $data['pagination']['total']);
            self::assertSame(array_slice($issues, ($page - 1) * 20, 20), array_column($data['list'], 'issue'));
            $collected = array_merge($collected, array_column($data['list'], 'issue'));
        }
        self::assertCount(120, array_unique($collected));
        $fresh = $this->history(['game' => 'jsssc', 'page_size' => 20]);
        self::assertSame('qa_history_new', $fresh['list'][0]['issue']);
        self::assertSame($first['pagination']['total'] + 2, $fresh['pagination']['total']);
        self::assertGreaterThan($snapshot, $fresh['pagination']['snapshot']);
    }

    /** 所有彩种历史行保持最新开奖的号码、大小单双和预测协议，且不串彩种。 */
    public function testEveryGameMatchesItsLatestDrawFormat(): void
    {
        foreach (array_keys(FeedCatalog::GAMES) as $code) {
            $draw = $this->draw($code, 'qa_history_format');
            $data = $this->history(['game' => $code]);
            $latest = $code === 'jnd28' ? (new ReferencePortalService())->open() : (new GamePortalService())->open($code);
            self::assertSame($code, $data['game']['code']);
            self::assertSame($latest['list'][0], $data['list'][0]);
            self::assertSame($draw->issue, $data['list'][0][$code === 'jnd28' ? 'period' : 'issue']);
            self::assertArrayNotHasKey('period', $data);
            self::assertArrayNotHasKey('prediction', $data);
        }
    }

    /** 加拿大28历史只显示对应期号开奖前生成的预测，保留真实命中。 */
    public function testCanadaPredictionIsMatchedAndNotBackfilled(): void
    {
        $draw = $this->draw('jnd28', '999000001001');
        $prediction = new Prediction([
            'game_id' => $draw->game_id, 'issue' => $draw->issue, 'source_issue' => '999000001000',
            'algorithm' => 'history-test', 'pdx' => '小', 'pds' => '双', 'sample_count' => 20,
            'basis_json' => '[]', 'basis_hash' => hash('sha256', '[]'), 'scores_json' => '{}',
            'target_time' => $draw->draw_time, 'create_time' => '2099-10-19 11:59:00',
        ]);
        self::assertTrue($prediction->save());
        $row = $this->history()['list'][0];
        self::assertTrue($row['prediction_available']);
        self::assertSame(1, $row['prdx']);
        self::assertSame(1, $row['prds']);

        $lateDraw = $this->draw('jnd28', '999000001002', '2099-10-19 12:01:00');
        $late = new Prediction($prediction->getAttributes(null, ['id']));
        $late->issue = $lateDraw->issue;
        $late->create_time = '2099-10-19 12:02:00';
        self::assertTrue($late->save());
        $row = $this->history()['list'][0];
        self::assertFalse($row['prediction_available']);
        self::assertNull($row['prdx']);
        self::assertSame('-', $row['pdx']);
    }

    /** 超末页钳制到最后一页；无记录快照仍返回第一页及零条记录。 */
    public function testLastPageAndEmptySnapshot(): void
    {
        $this->draw('az10', 'qa_history_last');
        $last = $this->history(['game' => 'az10', 'page' => 10000, 'page_size' => 20]);
        self::assertSame($last['pagination']['total_pages'], $last['pagination']['page']);
        self::assertNotEmpty($last['list']);
        self::assertLessThanOrEqual(20, count($last['list']));

        foreach (array_keys(FeedCatalog::GAMES) as $code) {
            $game = Game::findOne(['code' => $code]);
            if (!Draw::find()->where(['game_id' => $game->id])->andWhere(['<=', 'id', 1])->exists()) {
                $empty = $this->history(['game' => $code, 'snapshot' => 1, 'page' => 10000]);
                self::assertSame([], $empty['list']);
                self::assertSame(0, $empty['pagination']['total']);
                self::assertSame(1, $empty['pagination']['total_pages']);
                self::assertSame(1, $empty['pagination']['page']);
                return;
            }
        }
        self::fail('同一主键不可能属于全部彩种。');
    }

    /** 数组、非法页码、超大快照和任意彩种不得进入数据库查询。 */
    public function testInvalidInputsAreRejected(): void
    {
        $invalid = [
            'game' => ['unknown', '../config', ['jnd28'], '', null],
            'page' => [0, -1, 10001, '1.5', '1e2', [1], '', null],
            'page_size' => [0, 10, 101, '20.5', [20], '', null],
            'snapshot' => [-1, '1.1', '1e5', '9007199254740992', '999999999999999999999999', [1], '', null],
        ];
        foreach ($invalid as $attribute => $values) {
            foreach ($values as $value) {
                self::assertFalse((new DrawHistoryForm([$attribute => $value]))->validate(), $attribute . ': ' . json_encode($value));
            }
        }
        self::assertTrue((new DrawHistoryForm())->validate());
        self::assertTrue((new DrawHistoryForm(['page' => '10000', 'page_size' => '50', 'snapshot' => '0']))->validate());
    }

    /** 公开入口拒绝隐藏彩种及与采集白名单不一致的后台号码规则。 */
    public function testHiddenAndMisconfiguredGamesAreNotReadable(): void
    {
        $game = Game::findOne(['code' => 'jsssc']);
        foreach ([['enabled' => Game::HIDDEN], ['enabled' => Game::VISIBLE, 'ball_count' => 3]] as $attributes) {
            $game->setAttributes($attributes);
            self::assertTrue($game->save(false));
            try {
                $this->history(['game' => 'jsssc']);
                self::fail('隐藏或配置不匹配的彩种不应返回历史数据。');
            } catch (NotFoundHttpException $exception) {
                self::assertSame(404, $exception->statusCode);
            }
        }
    }
}
