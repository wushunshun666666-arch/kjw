<?php

use common\models\Error;
use common\models\lottery\{CollectionModel, CollectionState, Draw, DrawConflict, DrawInput, DrawModel, FeedGateway, Game};
use PHPUnit\Framework\TestCase;

/** 真实数据库事务回滚，验证冲突保护及后续期入库。 */
final class CollectionRecoveryTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;

    protected function setUp(): void { $this->transaction = Yii::$app->db->beginTransaction(); }
    protected function tearDown(): void { if ($this->transaction?->isActive) { $this->transaction->rollBack(); } }

    public function testConflictDetailsAndContinuedCollection(): void
    {
        $game = Game::findOne(['code' => 'jnd28']);
        $prefix = 'recovery_' . bin2hex(random_bytes(5));
        $input = new DrawInput(['game_id' => $game->id, 'issue' => $prefix . '_numbers',
            'numbers' => '1,2,3', 'draw_time' => '2020-01-01 00:00:00', 'source' => 'test']);
        self::assertInstanceOf(Draw::class, DrawModel::receive($input));
        $originalNumbers = Draw::findOne(['game_id' => $game->id, 'issue' => $input->issue])->attributes;
        $input->issue = $prefix . '_time';
        self::assertInstanceOf(Draw::class, DrawModel::receive($input));
        $originalTime = Draw::findOne(['game_id' => $game->id, 'issue' => $input->issue])->attributes;
        $input->draw_time = '2020-01-01 00:00:01';
        $conflict = DrawModel::receive($input);
        self::assertInstanceOf(DrawConflict::class, $conflict);
        self::assertStringContainsString('开奖时间不同', $conflict->getError());
        self::assertStringNotContainsString('号码不同', $conflict->getError());
        $input->numbers = '3,2,1';
        $both = DrawModel::receive($input);
        self::assertStringContainsString('号码不同', $both->getError());
        self::assertStringContainsString('开奖时间不同', $both->getError());
        $gateway = new class($prefix) extends FeedGateway {
            public function __construct(private string $prefix) {}
            public function fetch(int $gameId, string $code = 'jnd28'): array|Error
            {
                return $this->decode(json_encode(['code' => 'jnd28', 'data' => [
                    ['draw' => $this->prefix . '_numbers', 'results' => '9,8,7', 'open_time' => '2020-01-01 00:00:00', 'draw_status' => 'estimated'],
                    ['draw' => $this->prefix . '_time', 'results' => '1,2,3', 'open_time' => '2020-01-01 00:00:01'],
                    ['draw' => $this->prefix . '_new', 'results' => '4,5,6', 'open_time' => '2020-01-01 00:00:02', 'draw_status' => 'anything'],
                ]], JSON_THROW_ON_ERROR), $gameId, $code);
            }
        };
        $model = new CollectionModel($gateway);
        self::assertFalse($model->collect());
        self::assertStringContainsString($prefix . '_numbers', $model->getError());
        self::assertStringContainsString('库内 1,2,3；接口 9,8,7', $model->getError());
        self::assertStringContainsString($prefix . '_time', $model->getError());
        self::assertSame(['received' => 3, 'inserted' => 1], $model->getLastResult());
        self::assertSame('1,2,3', Draw::findOne(['game_id' => $game->id, 'issue' => $prefix . '_numbers'])->numbers);
        self::assertSame('2020-01-01 00:00:00', Draw::findOne(['game_id' => $game->id, 'issue' => $prefix . '_time'])->draw_time);
        self::assertSame('4,5,6', Draw::findOne(['game_id' => $game->id, 'issue' => $prefix . '_new'])->numbers);
        $state = CollectionState::findOne($game->id);
        self::assertNotSame('', $state->error);
        self::assertSame(1, (int) $state->inserted);
        self::assertFalse($model->collect());
        self::assertSame(['received' => 3, 'inserted' => 0], $model->getLastResult());
        self::assertSame($originalNumbers, Draw::findOne(['game_id' => $game->id, 'issue' => $prefix . '_numbers'])->attributes);
        self::assertSame($originalTime, Draw::findOne(['game_id' => $game->id, 'issue' => $prefix . '_time'])->attributes);
    }

    public function testDrawStatusIsCompletelyIgnored(): void
    {
        $game = Game::findOne(['code' => 'jnd28']);
        $gateway = new FeedGateway();
        foreach ([null, 'estimated', 'confirmed', '', false, ['unknown' => true]] as $status) {
            $row = ['draw' => 'status_ignored', 'results' => '1,2,3', 'open_time' => '2020-01-01 00:00:00'];
            $without = $gateway->decode(json_encode(['code' => 'jnd28', 'data' => [$row]]), (int) $game->id);
            $row['draw_status'] = $status;
            $with = $gateway->decode(json_encode(['code' => 'jnd28', 'data' => [$row]]), (int) $game->id);
            self::assertIsArray($with);
            self::assertSame($without[0]->getAttributes(), $with[0]->getAttributes());
        }
    }
}
