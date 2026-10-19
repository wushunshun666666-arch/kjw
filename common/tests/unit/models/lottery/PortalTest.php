<?php

use common\models\lottery\GameModel;
use common\models\lottery\Game;
use common\models\lottery\DrawModel;
use common\models\lottery\DrawInput;
use common\models\lottery\Draw;
use common\models\lottery\Audit;
use common\models\lottery\NoticeModel;
use common\models\lottery\FeedGateway;
use common\models\Error;
use frontend\models\PortalService;
use frontend\models\HistoryForm;
use PHPUnit\Framework\TestCase;

/** 门户领域集成验收，所有测试记录在事务内回滚。 */
final class PortalTest extends TestCase
{
    private ?\yii\db\Transaction $transaction = null;

    /** 隔离每个测试创建的数据。 */
    protected function setUp(): void
    {
        $this->transaction = Yii::$app->db->beginTransaction();
    }

    /** 回滚测试写入，保留已有实例数据。 */
    protected function tearDown(): void
    {
        if ($this->transaction?->isActive) { $this->transaction->rollBack(); }
    }

    /** 创建仅在事务内存在的彩种。 */
    private function game(): GameModel
    {
        $result = GameModel::create(['code' => 'test_' . bin2hex(random_bytes(6)), 'name' => '测试彩种',
            'description' => '', 'ball_count' => 3, 'ball_min' => 0, 'ball_max' => 9,
            'sort_order' => 0, 'enabled' => Game::VISIBLE]);
        self::assertInstanceOf(GameModel::class, $result);
        return $result;
    }

    /** 构造标准输入，不依赖实时 API。 */
    private function input(int $gameId): DrawInput
    {
        return new DrawInput(['game_id' => $gameId, 'issue' => 'test_001', 'numbers' => '1,2,3',
            'draw_time' => '2026-10-18 01:00:00', 'source' => '自动测试']);
    }

    /** 重试不重复入库，同一期冲突不覆盖原始数据。 */
    public function testReceiveIsIdempotentAndRejectsConflict(): void
    {
        $game = $this->game()->getData();
        $input = $this->input((int) $game->id);
        $first = DrawModel::receive($input);
        self::assertInstanceOf(Draw::class, $first);
        self::assertSame($first->id, DrawModel::receive($input)->id);
        $input->numbers = '3,2,1';
        self::assertInstanceOf(Error::class, DrawModel::receive($input));
        self::assertSame('1,2,3', Draw::findOne($first->id)->numbers);
        self::assertSame('1', (string) Draw::find()->where(['game_id' => $game->id])->count());
        self::assertSame('1', (string) Audit::find()->where(['entity_type' => 'draw', 'entity_id' => $first->id])->count());
    }

    /** 不合法的个数与范围均不会创建记录。 */
    public function testNumberBoundsAndMissingGameAreRejected(): void
    {
        $game = $this->game()->getData();
        $input = $this->input((int) $game->id);
        foreach (['1,2', '1,2,10', '-1,2,3', 'a,b,c', '1,2,3,4'] as $numbers) {
            $input->numbers = $numbers;
            self::assertInstanceOf(Error::class, DrawModel::receive($input));
        }
        $input = $this->input(2147483647);
        self::assertInstanceOf(Error::class, DrawModel::receive($input));
        self::assertSame('0', (string) Draw::find()->where(['game_id' => $game->id])->count());
    }

    /** 陈旧配置版本与已有开奖的规则变更都被拒绝。 */
    public function testConfigVersionAndHistoricalRulesAreProtected(): void
    {
        $model = $this->game();
        $data = $model->getData();
        $attributes = $data->attributes;
        unset($attributes['id'], $attributes['create_time'], $attributes['update_time']);
        $attributes['version'] = 99;
        self::assertFalse($model->modify($attributes));
        $input = $this->input((int) $data->id);
        self::assertInstanceOf(Draw::class, DrawModel::receive($input));
        $attributes['version'] = 0;
        $attributes['ball_max'] = 8;
        self::assertFalse($model->modify($attributes));
    }

    /** API 格式错误、错误彩种和缺字段不能进入标准数据层。 */
    public function testFeedProtocolIsValidated(): void
    {
        $gateway = new FeedGateway();
        foreach (['invalid', '{"code":"other","data":[]}', '{"code":"jnd28","data":[{}]}'] as $body) {
            self::assertInstanceOf(Error::class, $gateway->decode($body, 1));
        }
        $rows = $gateway->decode('{"code":"jnd28","data":[{"draw":"12","results":"2,5,2","open_time":"2026-10-18 03:39:30"}]}', 1);
        self::assertCount(1, $rows);
        self::assertSame('12', $rows[0]->issue);
        self::assertSame('2,5,2', $rows[0]->numbers);
    }

    /** 发布状态、日期和完整期号实际参与公开查询。 */
    public function testPublicFiltersAndHiddenGames(): void
    {
        $model = $this->game();
        $game = $model->getData();
        self::assertInstanceOf(Draw::class, DrawModel::receive($this->input((int) $game->id)));
        $service = new PortalService();
        $form = new HistoryForm(['game' => $game->code, 'date' => '2026-10-18', 'issue' => 'test_001']);
        self::assertTrue($form->validate());
        self::assertSame(1, $service->history($form)['total']);
        $form->date = '2026-10-17';
        self::assertSame(0, $service->history($form)['total']);
        $attributes = $game->attributes;
        unset($attributes['id'], $attributes['create_time'], $attributes['update_time']);
        $attributes['enabled'] = Game::HIDDEN;
        self::assertTrue($model->modify($attributes));
        $this->expectException(\yii\web\NotFoundHttpException::class);
        $service->history($form);
    }

    /** 未发布公告不能公开读取。 */
    public function testUnpublishedNoticeIsPrivate(): void
    {
        $model = NoticeModel::create(['title' => '<script>alert(1)</script>', 'content' => '正文', 'enabled' => 0]);
        self::assertInstanceOf(NoticeModel::class, $model);
        $this->expectException(\yii\web\NotFoundHttpException::class);
        (new PortalService())->notice((int) $model->getData()->id);
    }

    /** 非法日期与无界页码返回验证错误。 */
    public function testQueryInputIsBounded(): void
    {
        foreach ([['date' => '2026-02-30'], ['page' => -1], ['page' => 10001], ['game' => '../config']] as $attributes) {
            self::assertFalse((new HistoryForm($attributes))->validate());
        }
    }
}
