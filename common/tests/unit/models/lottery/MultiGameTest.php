<?php

use common\models\Error;
use common\models\lottery\CollectionModel;
use common\models\lottery\CollectionState;
use common\models\lottery\Draw;
use common\models\lottery\DrawInput;
use common\models\lottery\DrawModel;
use common\models\lottery\FeedCatalog;
use common\models\lottery\FeedGateway;
use common\models\lottery\Game;
use frontend\models\GameForm;
use frontend\models\GamePortalService;
use PHPUnit\Framework\TestCase;

/** 多来源协议、隔离、故障恢复与公开数据验收，所有测试写入回滚。 */
final class MultiGameTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;

    /** 每例事务隔离真实实例。 */
    protected function setUp(): void { $this->transaction = Yii::$app->db->beginTransaction(); }

    /** 不遗留模拟开奖与采集状态。 */
    protected function tearDown(): void { if ($this->transaction?->isActive) { $this->transaction->rollBack(); } }

    /** 使用已观察到的两种上游协议构造有界测试数据。 */
    private function body(string $code, string $issue = 'qa_multigame_01'): string
    {
        $definition = FeedCatalog::get($code);
        $numbers = $definition['type'] === 'racing' ? '02,04,09,03,07,01,10,05,08,06'
            : ($definition['count'] === 5 ? '0,8,7,1,2' : '2,5,2');
        $row = $code === 'jnd28' ? ['draw' => $issue, 'results' => $numbers, 'open_time' => '2026-10-19 09:08:00']
            : ['expect' => $issue, 'opencode' => $numbers, 'opentime' => '2026-10-19 09:08:00'];
        return json_encode(['code' => $code, 'data' => [$row]], JSON_THROW_ON_ERROR);
    }

    /** 六种来源按白名单选择，保持原号码顺序并拒绝非法协议。 */
    public function testSourceProtocolsAndNumberRules(): void
    {
        $gateway = new FeedGateway();
        foreach (array_keys(FeedCatalog::GAMES) as $code) {
            $game = Game::findOne(['code' => $code]);
            self::assertNotNull($game);
            self::assertTrue(FeedCatalog::matches($game));
            $rows = $gateway->decode($this->body($code), (int) $game->id, $code);
            self::assertIsArray($rows);
            self::assertCount(1, $rows);
            self::assertSame('qa_multigame_01', $rows[0]->issue);
            self::assertSame((int) $game->id, $rows[0]->game_id);
            self::assertInstanceOf(Error::class, $gateway->decode('', (int) $game->id, $code));
            self::assertInstanceOf(Error::class, $gateway->decode('{"data":[]}', (int) $game->id, $code));
            self::assertInstanceOf(Error::class, $gateway->decode('{"code":"wrong","data":[]}', (int) $game->id, $code));
        }
        foreach (['1,1,3,4,5,6,7,8,9,10', '0,2,3,4,5,6,7,8,9,10', '1,2,3', '11,2,3,4,5,6,7,8,9,10'] as $bad) {
            $body = str_replace('02,04,09,03,07,01,10,05,08,06', $bad, $this->body('jspk10'));
            self::assertInstanceOf(Error::class, $gateway->decode($body, 1, 'jspk10'));
        }
        self::assertStringEndsWith('gamename=az51&nums=1', FeedCatalog::url('az51'));
        self::assertStringEndsWith('gamename=az10&nums=1', FeedCatalog::url('az10'));
        self::assertInstanceOf(Error::class, $gateway->decode($this->body('jsssc'), 1, '../config'));
    }

    /** 共享期号只在所属彩种内去重，前导零标准化不改变位置。 */
    public function testDrawIsolationAndRacingPermutation(): void
    {
        $gateway = new FeedGateway();
        foreach (['jspk10', 'jsft_168', 'jsssc'] as $code) {
            $game = Game::findOne(['code' => $code]);
            $input = $gateway->decode($this->body($code), (int) $game->id, $code)[0];
            $first = DrawModel::receive($input);
            self::assertInstanceOf(Draw::class, $first);
            self::assertSame($first->id, DrawModel::receive($input)->id);
            self::assertSame(1, (int) Draw::find()->where(['game_id' => $game->id, 'issue' => $input->issue])->count());
            if ($code !== 'jsssc') {
                self::assertSame('2,4,9,3,7,1,10,5,8,6', $first->numbers);
                $input->issue = 'qa_multigame_invalid';
                $input->numbers = '1,1,3,4,5,6,7,8,9,10';
                self::assertInstanceOf(Error::class, DrawModel::receive($input));
            }
        }
        self::assertSame(3, (int) Draw::find()->where(['issue' => 'qa_multigame_01'])->count());
    }

    /** 失败来源不会阻断后续来源，恢复后清除错误且不会重复入库。 */
    public function testPartialFailureRecoveryAndIdempotency(): void
    {
        $bodies = [];
        foreach (array_keys(FeedCatalog::GAMES) as $code) { $bodies[$code] = $this->body($code, 'qa_collection_01'); }
        $gateway = new class($bodies) extends FeedGateway {
            public bool $fail = true;
            /** 固定模拟响应，不使用网络。 */
            public function __construct(private array $bodies) {}
            /** 单一来源故障，其他来源继续返回正常协议。 */
            public function fetch(int $gameId, string $code = 'jnd28'): array|Error
            {
                return $this->decode($this->fail && $code === 'az51' ? '' : $this->bodies[$code], $gameId, $code);
            }
        };
        $model = new CollectionModel($gateway);
        $first = $model->collectAll();
        self::assertSame(6, $first['inserted']);
        self::assertSame(['az51'], array_keys($first['errors']));
        $id = (int) Game::findOne(['code' => 'az51'])->id;
        self::assertNotSame('', CollectionState::findOne($id)->error);
        $gateway->fail = false;
        $second = $model->collectAll();
        self::assertSame(1, $second['inserted']);
        self::assertSame([], $second['errors']);
        self::assertSame('', CollectionState::findOne($id)->error);
        self::assertNotNull(CollectionState::findOne($id)->success_time);
        self::assertSame(0, $model->collectAll()['inserted']);
        self::assertFalse($model->collect('https://example.com/'));
    }

    /** 只读接口使用彩种规则计算总和/冠亚和，隐藏彩种没有公开结果。 */
    public function testPublicGameDataAndPublicationBoundary(): void
    {
        $service = new GamePortalService();
        foreach (['jspk10' => [6, '冠亚和', '小'], 'jsssc' => [18, '总和', '小']] as $code => [$sum, $label, $size]) {
            $game = Game::findOne(['code' => $code]);
            $input = (new FeedGateway())->decode($this->body($code), (int) $game->id, $code)[0];
            $input->draw_time = '2099-10-19 09:08:00';
            self::assertInstanceOf(Draw::class, DrawModel::receive($input));
            $data = $service->open($code);
            self::assertSame($code, $data['game']['code']);
            self::assertSame($label, $data['game']['sum_label']);
            self::assertSame($sum, $data['list'][0]['sum']);
            self::assertSame($size, $data['list'][0]['size']);
            self::assertArrayNotHasKey('prediction', $data);
        }
        $game->enabled = Game::HIDDEN;
        self::assertTrue($game->save());
        self::assertNotContains('jsssc', array_column($service->games(), 'code'));
        $this->expectException(\yii\web\NotFoundHttpException::class);
        $service->open('jsssc');
    }

    /** 数组、任意 URL 与未知彩种不能作为公开查询参数。 */
    public function testPublicQueryInput(): void
    {
        foreach (['unknown', '../config', 'https://example.com', ['jsssc']] as $value) {
            self::assertFalse((new GameForm(['game' => $value]))->validate());
        }
        self::assertTrue((new GameForm(['game' => 'jsssc']))->validate());
    }

    /** 六个彩种提供总和或冠亚和、大小单双及全部位置，不混用两类号码范围。 */
    public function testAnalysisOptionsForEveryGame(): void
    {
        foreach (['az51', 'xyft_168', 'jspk10', 'jsssc', 'jsft_168', 'az10'] as $code) {
            $data = (new GamePortalService())->open($code);
            $racing = $data['game']['type'] === 'racing';
            $options = array_column($data['game']['analysis'], null, 'key');
            self::assertSame($racing ? '冠亚和' : '总和', $options['sum']['label']);
            self::assertSame($racing ? range(3, 19) : range(0, 45), $options['sum']['values']);
            self::assertSame(['大', '小'], $options['size']['values']);
            self::assertSame(['单', '双'], $options['parity']['values']);
            self::assertCount($racing ? 13 : 8, $options);
            foreach ($data['game']['positions'] as $position => $label) {
                self::assertSame($label, $options['position-' . $position]['label']);
                self::assertSame($position, $options['position-' . $position]['position']);
                self::assertSame($racing ? range(1, 10) : range(0, 9), $options['position-' . $position]['values']);
            }
        }
    }
}
