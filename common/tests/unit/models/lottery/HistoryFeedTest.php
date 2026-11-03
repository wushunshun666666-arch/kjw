<?php

use common\models\Error;
use common\models\lottery\{CollectionModel, Draw, FeedCatalog, FeedGateway, Game};
use PHPUnit\Framework\TestCase;

/** 新历史源的实际响应、彩种身份和历史保护，测试写入全部回滚。 */
final class HistoryFeedTest extends TestCase
{
    public function testEveryGameUsesVerifiedUnifiedHistoryEndpoint(): void
    {
        $codes = self::CODES + ['hk6' => '10048', 'macau_new' => 'macau_new', 'macau_old' => 'macau_old'];
        self::assertSame(count(FeedCatalog::GAMES), count($codes));
        foreach ($codes as $code => $lotCode) {
            self::assertSame('http://69.165.75.20:86/lottery/api/history?lotCode=' . $lotCode, FeedCatalog::url($code));
        }
    }

    private const CODES = ['jnd28' => 'jnd28', 'az51' => '10010', 'az10' => '10012', 'xyft_168' => '10057',
        'jsft_168' => '10035', 'jsssc' => '10036', 'jspk10' => '10037'];
    private ?\yii\db\Transaction $transaction = null;

    protected function setUp(): void { $this->transaction = Yii::$app->db->beginTransaction(); }
    protected function tearDown(): void { if ($this->transaction?->isActive) { $this->transaction->rollBack(); } }

    private function payload(string $lotCode): array
    {
        return json_decode(file_get_contents(dirname(__DIR__, 3) . '/fixtures/history-feed/' . $lotCode . '.json'), true);
    }

    public function testAllSevenRealResponsesPreserveOrderAndIgnoreDrawStatus(): void
    {
        $gateway = new FeedGateway();
        foreach (self::CODES as $code => $lotCode) {
            self::assertSame('http://69.165.75.20:86/lottery/api/history?lotCode=' . $lotCode, FeedCatalog::url($code));
            $payload = $this->payload($lotCode);
            $rows = $gateway->decode(json_encode($payload), 1, $code);
            self::assertIsArray($rows);
            self::assertCount(20, $rows);
            $newest = $rows[count($rows) - 1];
            self::assertSame($payload['data']['list'][0]['draw_code'], $newest->numbers);
            self::assertSame($payload['data']['list'][0]['issue'], $newest->issue);
            self::assertSame($payload['data']['list'][0]['draw_time'], $newest->draw_time);
            foreach ([null, 'estimated', 'confirmed', false, ['arbitrary']] as $status) {
                $payload['draw_status'] = $status;
                foreach ($payload['data']['list'] as &$row) { $row['draw_status'] = $status; }
                unset($row);
                self::assertEquals($rows, $gateway->decode(json_encode($payload), 1, $code));
            }
            $payload['data']['lotCode'] = $lotCode === '10010' ? '10036' : '10010';
            self::assertInstanceOf(Error::class, $gateway->decode(json_encode($payload), 1, $code));
        }
    }

    public function testRepeatedCollectionAndConflictsNeverOverwriteStoredDraws(): void
    {
        foreach (self::CODES as $code => $lotCode) {
            $game = Game::findOne(['code' => $code]);
            $gateway = new class extends FeedGateway {
                public array $payload;
                public function fetch(int $gameId, string $code = 'jnd28'): array|Error
                { return $this->decode(json_encode($this->payload), $gameId, $code); }
            };
            $gateway->payload = $this->payload($lotCode);
            $model = new CollectionModel($gateway);
            $first = $model->collect($code);
            self::assertIsArray($first);
            self::assertSame(20, $first['received']);
            self::assertSame(0, $model->collect($code)['inserted']);
            $before = Draw::find()->where(['game_id' => $game->id])->orderBy('id')->asArray()->all();
            $rows = &$gateway->payload['data']['list'];
            $numbers = explode(',', $rows[0]['draw_code']);
            // 选择不同位置交换，保持号码数量、范围及赛车唯一性有效。
            $swap = array_search(true, array_map(static fn ($n): bool => $n !== $numbers[0], $numbers), true);
            self::assertNotFalse($swap);
            [$numbers[0], $numbers[$swap]] = [$numbers[$swap], $numbers[0]];
            $rows[0]['draw_code'] = implode(',', $numbers);
            $rows[1]['draw_time'] = '2020-01-01 00:00:00';
            $rows[] = ['issue' => 'qa_unified_source_new', 'draw_code' => $rows[2]['draw_code'],
                'draw_time' => '2099-01-01 00:00:00', 'draw_status' => 'ignored'];
            unset($rows);
            self::assertFalse($model->collect($code));
            self::assertSame(1, $model->getLastResult()['inserted']);
            self::assertStringContainsString('号码不同', $model->getError());
            self::assertStringContainsString('开奖时间不同', $model->getError());
            self::assertSame($before, Draw::find()->where(['id' => array_column($before, 'id')])->orderBy('id')->asArray()->all());
            self::assertNotNull(Draw::findOne(['game_id' => $game->id, 'issue' => 'qa_unified_source_new']));
        }
    }

    public function testMalformedHistoryCannotDowngradeToLegacyProtocol(): void
    {
        $gateway = new FeedGateway();
        foreach (self::CODES as $code => $lotCode) {
            foreach ([['code' => $code, 'data' => []], ['code' => 1000, 'data' => null],
                ['code' => 1000, 'data' => ['lotCode' => $lotCode, 'list' => []]],
                ['code' => 1000, 'data' => ['lotCode' => $lotCode, 'list' => [null]]]] as $payload) {
                self::assertInstanceOf(Error::class, $gateway->decode(json_encode($payload), 1, $code));
            }
        }
    }
}
