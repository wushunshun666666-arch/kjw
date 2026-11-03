<?php

use common\models\Error;
use common\models\lottery\{CollectionModel, Draw, DrawConflict, DrawModel, FeedCatalog, FeedGateway, Game, SharedDataPayload};
use frontend\models\{DrawHistoryForm, DrawHistoryService, GamePortalService};
use PHPUnit\Framework\TestCase;

/** 使用实际响应样本，在事务内验证三种六合彩完整链路。 */
final class MarksixTest extends TestCase
{
    public function testSharedMetadataHelpersDoNotChangeExistingCanadaRules(): void
    {
        $data = (new GamePortalService())->open('jnd28');
        self::assertSame(['第一位', '第二位', '第三位'], $data['game']['positions']);
        self::assertSame(range(0, 27), $data['game']['analysis'][0]['values']);
    }

    public function testConflictingHistoryDoesNotBlockNewDrawsForAnyMarksixGame(): void
    {
        foreach (['hk6', 'macau_new', 'macau_old'] as $code) {
            $game = Game::findOne(['code' => $code]);
            $gateway = new class extends FeedGateway {
                public array $payload;
                public function fetch(int $gameId, string $code = 'jnd28'): array|Error
                { return $this->decode(json_encode($this->payload), $gameId, $code); }
            };
            $gateway->payload = json_decode($this->body($code), true);
            $model = new CollectionModel($gateway);
            self::assertIsArray($model->collect($code));
            $before = Draw::find()->where(['game_id' => $game->id])->orderBy('id')->asArray()->all();
            $rows = &$gateway->payload['data']['list'];
            $balls = explode(',', $rows[0]['draw_code']);
            [$balls[0], $balls[1]] = [$balls[1], $balls[0]];
            $rows[0]['draw_code'] = implode(',', $balls);
            $rows[1]['draw_time'] = '2020-01-01 00:00:00';
            $rows[] = ['issue' => 'qa_marksix_new', 'draw_code' => '01,02,03,04,05,06,49',
                'draw_time' => '2099-01-01 00:00:00', 'draw_status' => ['ignored']];
            unset($rows);
            self::assertFalse($model->collect($code));
            self::assertSame(1, $model->getLastResult()['inserted']);
            self::assertStringContainsString('号码不同', $model->getError());
            self::assertStringContainsString('开奖时间不同', $model->getError());
            self::assertSame($before, Draw::find()->where(['id' => array_column($before, 'id')])->orderBy('id')->asArray()->all());
            self::assertSame('1,2,3,4,5,6,49', Draw::findOne(['game_id' => $game->id, 'issue' => 'qa_marksix_new'])->numbers);
            self::assertFalse($model->collect($code));
            self::assertSame(0, $model->getLastResult()['inserted']);
        }
    }

    public function testEachMarksixHistoryRetainsSnapshotAcrossNewCollection(): void
    {
        foreach (['hk6', 'macau_new', 'macau_old'] as $code) {
            $game = Game::findOne(['code' => $code]);
            $input = (new FeedGateway())->decode($this->body($code), (int) $game->id, $code)[0];
            $expected = [];
            for ($index = 0; $index < 25; $index++) {
                $input->issue = 'qa_marksix_page_' . $index;
                $input->draw_time = '2099-01-01 00:00:' . sprintf('%02d', $index);
                self::assertInstanceOf(Draw::class, DrawModel::receive($input));
                $expected[] = $input->issue;
            }
            $service = new DrawHistoryService();
            $first = $service->search(new DrawHistoryForm(['game' => $code, 'page_size' => 20]));
            self::assertSame(25, $first['pagination']['total']);
            $input->issue = 'qa_marksix_page_new'; $input->draw_time = '2099-01-01 00:01:00';
            self::assertInstanceOf(Draw::class, DrawModel::receive($input));
            $second = $service->search(new DrawHistoryForm(['game' => $code, 'page_size' => 20,
                'page' => 2, 'snapshot' => $first['pagination']['snapshot']]));
            self::assertSame(25, $second['pagination']['total']);
            self::assertSame(array_reverse($expected), array_column(array_merge($first['list'], $second['list']), 'issue'));
            self::assertCount(7, $second['list'][0]['balls']);
            $fresh = $service->search(new DrawHistoryForm(['game' => $code, 'page_size' => 20]));
            self::assertSame(26, $fresh['pagination']['total']);
            self::assertSame('qa_marksix_page_new', $fresh['list'][0]['issue']);
        }
    }

    private ?\yii\db\Transaction $transaction = null;

    protected function setUp(): void
    {
        $this->transaction = Yii::$app->db->beginTransaction();
        require_once dirname(__DIR__, 5) . '/console/migrations/m261103_000001_add_marksix_games.php';
        ob_start();
        try { (new m261103_000001_add_marksix_games(['db' => Yii::$app->db]))->safeUp(); }
        finally { ob_end_clean(); }
    }

    protected function tearDown(): void { if ($this->transaction?->isActive) { $this->transaction->rollBack(); } }

    private function body(string $code): string
    {
        return file_get_contents(dirname(__DIR__, 3) . '/fixtures/marksix/' . FeedCatalog::get($code)['query'] . '.json');
    }

    public function testRealResponsesCollectAndPublishWithoutOverwritingHistory(): void
    {
        foreach (['hk6' => 6, 'macau_new' => 16, 'macau_old' => 16] as $code => $count) {
            $game = Game::findOne(['code' => $code]);
            $body = $this->body($code);
            $gateway = new class($body) extends FeedGateway {
                public function __construct(private string $body) {}
                public function fetch(int $gameId, string $code = 'jnd28'): array|Error { return $this->decode($this->body, $gameId, $code); }
            };
            $model = new CollectionModel($gateway);
            self::assertIsArray($model->collect($code));
            $stored = Draw::find()->where(['game_id' => $game->id])->orderBy('id')->asArray()->all();
            self::assertCount($count, $stored);
            self::assertSame(0, $model->collect($code)['inserted']);
            $rows = $gateway->fetch((int) $game->id, $code);
            $rows[0]->numbers = implode(',', array_reverse(explode(',', $rows[0]->numbers)));
            self::assertInstanceOf(DrawConflict::class, DrawModel::receive($rows[0]));
            $rows[0] = $gateway->fetch((int) $game->id, $code)[0];
            $rows[0]->draw_time = '2020-01-01 00:00:00';
            self::assertInstanceOf(DrawConflict::class, DrawModel::receive($rows[0]));
            self::assertSame($stored, Draw::find()->where(['game_id' => $game->id])->orderBy('id')->asArray()->all());
            $latest = (new GamePortalService())->open($code);
            self::assertCount(7, $latest['game']['positions']);
            self::assertSame('特码', $latest['game']['positions'][6]);
            self::assertNull($latest['period']['next_draw_at']);
            self::assertNull($latest['period']['interval_seconds']);
            $sourceLatest = json_decode($body, true)['data']['list'][0];
            self::assertSame(array_map('intval', explode(',', $sourceLatest['draw_code'])), $latest['list'][0]['balls']);
            $envelope = ['version' => 1, 'code' => 200, 'data' => $latest];
            self::assertSame($latest['list'], SharedDataPayload::validate($envelope, 'latest', ['game' => $code])['list']);
            $history = (new DrawHistoryService())->search(new DrawHistoryForm(['game' => $code, 'page_size' => 20]));
            self::assertSame($latest['list'], $history['list']);
        }
    }

    public function testRejectsWrongGameIncompleteRepeatedAndInvalidNumbers(): void
    {
        $gateway = new FeedGateway();
        $body = json_decode($this->body('macau_old'), true);
        foreach (['1,2,3,4,5,6', '1,2,3,4,5,6,6', '0,2,3,4,5,6,7', '1,2,3,4,5,6,50', '1,2,3,4,5,6,7,8', 'a,2,3,4,5,6,7'] as $numbers) {
            $bad = $body; $bad['data']['list'][0]['draw_code'] = $numbers;
            self::assertInstanceOf(Error::class, $gateway->decode(json_encode($bad), 1, 'macau_old'));
        }
        self::assertInstanceOf(Error::class, $gateway->decode(json_encode($body), 1, 'macau_new'));
        $body['code'] = 200;
        self::assertInstanceOf(Error::class, $gateway->decode(json_encode($body), 1, 'macau_old'));
        $body = json_decode($this->body('hk6'), true);
        $normal = $gateway->decode(json_encode($body), 1, 'hk6');
        $body['draw_status'] = 'estimated';
        foreach ($body['data']['list'] as &$row) { $row['draw_status'] = ['ignored' => true]; }
        unset($row);
        self::assertEquals($normal, $gateway->decode(json_encode($body), 1, 'hk6'));
    }

    public function testMigrationPreservesExistingConfigurationAndDoesNotDuplicate(): void
    {
        $game = Game::findOne(['code' => 'macau_old']);
        $game->name = '运营自定义'; $game->enabled = 0;
        self::assertTrue($game->save());
        $game->refresh();
        $before = $game->attributes;
        ob_start();
        try { (new m261103_000001_add_marksix_games(['db' => Yii::$app->db]))->safeUp(); }
        finally { ob_end_clean(); }
        self::assertSame($before, Game::findOne($game->id)->attributes);
        self::assertSame(1, (int) Game::find()->where(['code' => 'macau_old'])->count());
    }
}
