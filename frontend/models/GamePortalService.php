<?php

namespace frontend\models;

use common\models\lottery\CollectionState;
use common\models\lottery\Draw;
use common\models\lottery\DrawSchedule;
use common\models\lottery\FeedCatalog;
use common\models\lottery\Game;
use common\models\lottery\MarksixAttributes;
use common\models\lottery\SharedDataConfig;
use common\models\lottery\SharedDataGateway;
use yii\web\NotFoundHttpException;

/** 新增彩种公开查询，所有开奖只读取数据库，绝不在页面访问时采集。 */
final class GamePortalService
{
    private SharedDataConfig $sharedConfig;
    private ?SharedDataGateway $sharedGateway;

    /** 注入部署模式和只读数据网关，分站不查询本地开奖表。 */
    public function __construct(?SharedDataConfig $sharedConfig = null, ?SharedDataGateway $sharedGateway = null)
    {
        $this->sharedConfig = $sharedConfig ?? SharedDataConfig::current();
        $this->sharedGateway = $sharedGateway;
    }

    /** 返回已发布且已接入来源的彩种导航，顺序与后台一致。 */
    public function games(): array
    {
        return Game::find()->select(['code', 'name'])->where(['enabled' => Game::VISIBLE, 'code' => array_keys(FeedCatalog::GAMES)])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->asArray()->all();
    }

    /** 返回单一彩种最近100期与号码规则，不复用加拿大28的和值/预测规则。 */
    public function open(string $code, bool $sharedExport = false): array
    {
        $criteria = ['code' => $code];
        if (!$sharedExport) { $criteria['enabled'] = Game::VISIBLE; }
        $game = Game::findOne($criteria);
        if ($game === null || !FeedCatalog::matches($game)) { throw new NotFoundHttpException('彩种不存在或尚未发布。'); }
        $definition = FeedCatalog::get($code);
        $racing = $definition['type'] === 'racing';
        $positions = FeedCatalog::positions($code);
        $marksix = $definition['type'] === 'marksix';
        $shared = !$sharedExport && $this->sharedConfig->isClient();
        if ($shared) {
            $data = ($this->sharedGateway ?? new SharedDataGateway($this->sharedConfig))->fetch('latest', ['game' => $code]);
            $rows = $data['list'];
            $collection = $data['collection'];
        } else {
            $draws = Draw::find()->select(['issue', 'numbers', 'draw_time'])->where(['game_id' => $game->id])
                ->orderBy(['draw_time' => SORT_DESC, 'id' => SORT_DESC])->limit(100)->all();
            $rows = $this->formatDraws($draws, $code);
            $state = CollectionState::findOne((int) $game->id);
            $collection = ['attempt_time' => $state?->attempt_time, 'success_time' => $state?->success_time,
                'unavailable' => $state !== null && $state->error !== ''];
        }
        $result = [
            'game' => ['code' => $code, 'name' => $game->name, 'type' => $definition['type'],
                'positions' => $positions, 'range' => $game->getNumberRange(), 'sum_label' => $racing ? '冠亚和' : '总和',
                'analysis' => $this->analysisOptions($game, $positions, $racing),
                'rule' => $marksix ? '前六位正码，第七位特码，01–49不重复。生肖按开奖当天北京时间的农历年份计算，正月初一换年；颜色对应固定红蓝绿波表。特码01–24小、25–48大，49的大小/单双/组合均为和；总和包含七球，175–322大、28–174小。仅作分类展示，不含投注结算，不推算下期开奖。' : ($racing ? '按第1至第10名排列；冠亚和为前两名之和，12–19为大，3–11为小。'
                    : ($definition['count'] === 5 ? '按万、千、百、十、个位排列；总和23–45为大，0–22为小。' : '三位号码之和14–27为大，0–13为小。'))],
            'list' => $rows,
            'period' => $marksix ? ['server_time' => time(), 'next_draw_at' => null, 'interval_seconds' => null]
                : DrawSchedule::estimate(array_column($rows, 'time')),
            'collection' => array_merge($collection, \common\models\lottery\CollectionHealth::describe(array_column($rows, 'time'), null, $marksix ? ($code === 'hk6' ? 7 * 86400 : 86400) : null)),
        ];
        // 对旧分站保持原有分析元信息长度；新分站在白名单校验后补充本地展示分类。
        if ($sharedExport && $marksix) { $result['game']['analysis'] = array_slice($result['game']['analysis'], 0, 3 + count($positions)); }
        if ($shared) { $result['shared_data'] = $data['shared_data']; }
        return $result;
    }

    /**
     * 最新开奖与历史分页共用号码、总和或冠亚和及大小单双规则。
     *
     * @param Draw[] $draws 同一已发布彩种的开奖记录
     */
    public function formatDraws(array $draws, string $code): array
    {
        $definition = FeedCatalog::get($code);
        if ($definition === null) {
            throw new NotFoundHttpException('彩种不存在或尚未发布。');
        }
        $racing = $definition['type'] === 'racing';
        $boundary = $racing ? 12 : ($definition['type'] === 'marksix' ? 175 : ($definition['count'] === 5 ? 23 : 14));
        $rows = [];
        foreach ($draws as $draw) {
            $balls = $draw->getBalls();
            $sum = $racing ? $balls[0] + $balls[1] : array_sum($balls);
            $row = ['issue' => $draw->issue, 'time' => $draw->draw_time, 'balls' => $balls,
                'sum' => $sum, 'size' => $sum >= $boundary ? '大' : '小', 'parity' => $sum % 2 === 0 ? '双' : '单'];
            if ($definition['type'] === 'marksix') { $row['marksix'] = MarksixAttributes::calculate($balls, $draw->draw_time); }
            $rows[] = $row;
        }
        return $rows;
    }

    /** 提供走势与统计共用的分析项目，大小单双沿用开奖结果的总和分类。 */
    private function analysisOptions(Game $game, array $positions, bool $racing): array
    {
        [$sumMin, $sumMax] = FeedCatalog::sumRange($game->code);
        $options = [
            ['key' => 'sum', 'label' => $racing ? '冠亚和' : '总和', 'field' => 'sum', 'values' => range($sumMin, $sumMax)],
            ['key' => 'size', 'label' => '大小', 'field' => 'size', 'values' => ['大', '小']],
            ['key' => 'parity', 'label' => '单双', 'field' => 'parity', 'values' => ['单', '双']],
        ];
        foreach ($positions as $index => $label) {
            $options[] = ['key' => 'position-' . $index, 'label' => $label, 'field' => 'balls',
                'position' => $index, 'values' => $game->getNumberRange()];
        }
        return $game->ball_count == 7 && FeedCatalog::get($game->code)['type'] === 'marksix'
            ? array_merge($options, MarksixAttributes::analysisOptions()) : $options;
    }
}
