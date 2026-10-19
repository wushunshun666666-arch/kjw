<?php

use common\models\lottery\{Game, Draw, Prediction, PredictionAlgorithm, PredictionModel};
use PHPUnit\Framework\TestCase;

/** 本地算法、事前生成与不可回改的数据库集成测试。 */
final class PredictionTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;

    /** 真实表结构、事务隔离，不保留测试开奖。 */
    protected function setUp(): void { $this->transaction = Yii::$app->db->beginTransaction(); }

    /** 恢复本地真实数据。 */
    protected function tearDown(): void { $this->transaction?->rollBack(); }

    /** 构造确定的最近二十期数据，全部只在本测试事务内存在。 */
    private function history(int $age = 30, int $count = 20): int
    {
        $game = Game::findOne(['code' => 'jnd28']);
        $game->enabled = Game::VISIBLE;
        self::assertTrue($game->save());
        Prediction::deleteAll(['game_id' => $game->id]);
        Draw::deleteAll(['game_id' => $game->id]);
        for ($i = 0; $i < $count; $i++) {
            $draw = new Draw(['game_id' => $game->id, 'issue' => (string) (9900000 - $i), 'numbers' => '9,9,9',
                'draw_time' => date('Y-m-d H:i:s', time() - $age - 210 * $i),
                'source' => '隔离测试', 'create_time' => date('Y-m-d H:i:s')]);
            self::assertTrue($draw->save());
        }
        return (int) $game->id;
    }

    /** 同一历史重复计算相同；近期权重可超过更早的多数样本。 */
    public function testWeightedAlgorithmIsDeterministic(): void
    {
        $rows = [];
        for ($i = 0; $i < 20; $i++) { $rows[] = ['issue' => (string) (20 - $i), 'numbers' => $i < 8 ? '9,9,9' : '0,0,0', 'draw_time' => '2026-01-01 00:00:00']; }
        $algorithm = new PredictionAlgorithm();
        $result = $algorithm->calculate($rows);
        self::assertSame('大', $result['pdx']);
        self::assertSame('单', $result['pds']);
        self::assertSame($result, $algorithm->calculate($rows));
        self::assertNull($algorithm->calculate(array_slice($rows, 0, 19)));
        self::assertSame(100, $algorithm->calculate(array_merge($rows, $rows, $rows, $rows, $rows, $rows))['sample_count']);
    }

    /** 每期只生成一次，保留真实来源和算法快照；重复轮询或补采不回改。 */
    public function testGenerationIsStoredIdempotentAndAuditable(): void
    {
        $gameId = $this->history();
        $prediction = PredictionModel::generateNext($gameId);
        self::assertInstanceOf(Prediction::class, $prediction);
        self::assertSame('9900001', $prediction->issue);
        self::assertSame('9900000', $prediction->source_issue);
        self::assertSame(20, (int) $prediction->sample_count);
        self::assertSame(hash('sha256', $prediction->basis_json), $prediction->basis_hash);
        self::assertCount(20, json_decode($prediction->basis_json, true));
        self::assertLessThan($prediction->target_time, $prediction->create_time);
        self::assertSame($prediction->id, PredictionModel::generateNext($gameId)->id);
        self::assertSame(1, (int) Prediction::find()->where(['game_id' => $gameId])->count());
        $prediction->pdx = '小';
        $this->expectException(\yii\base\InvalidCallException::class);
        $prediction->save();
    }

    /** 历史导入、未来数据和小样本不能生成冒充事前的预测。 */
    public function testExpiredFutureAndShortHistoryAreSkipped(): void
    {
        self::assertNull(PredictionModel::generateNext($this->history(500)));
        self::assertNull(PredictionModel::generateNext($this->history(-30)));
        self::assertNull(PredictionModel::generateNext($this->history(30, 19)));
    }

    /** 最新目标期号已存在时拒绝预测，即使记录时间异常。 */
    public function testKnownTargetIsNeverPredicted(): void
    {
        $gameId = $this->history();
        $known = new Draw(['game_id' => $gameId, 'issue' => '9900001', 'numbers' => '1,2,3',
            'draw_time' => date('Y-m-d H:i:s', time() - 60), 'source' => '隔离测试', 'create_time' => date('Y-m-d H:i:s')]);
        self::assertTrue($known->save());
        self::assertNull(PredictionModel::generateNext($gameId));
    }
}
