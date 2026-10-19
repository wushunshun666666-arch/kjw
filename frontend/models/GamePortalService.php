<?php

namespace frontend\models;

use common\models\lottery\CollectionState;
use common\models\lottery\Draw;
use common\models\lottery\DrawSchedule;
use common\models\lottery\FeedCatalog;
use common\models\lottery\Game;
use yii\web\NotFoundHttpException;

/** 新增彩种公开查询，所有开奖只读取数据库，绝不在页面访问时采集。 */
final class GamePortalService
{
    /** 返回已发布且已接入来源的彩种导航，顺序与后台一致。 */
    public function games(): array
    {
        return Game::find()->select(['code', 'name'])->where(['enabled' => Game::VISIBLE, 'code' => array_keys(FeedCatalog::GAMES)])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->asArray()->all();
    }

    /** 返回单一彩种最近100期与号码规则，不复用加拿大28的和值/预测规则。 */
    public function open(string $code): array
    {
        $game = Game::findOne(['code' => $code, 'enabled' => Game::VISIBLE]);
        if ($game === null || !FeedCatalog::matches($game)) { throw new NotFoundHttpException('彩种不存在或尚未发布。'); }
        $definition = FeedCatalog::get($code);
        $racing = $definition['type'] === 'racing';
        $positions = $racing ? array_map(static fn (int $rank): string => '第' . $rank . '名', range(1, 10))
            : ($definition['count'] === 5 ? ['万位', '千位', '百位', '十位', '个位'] : ['第一位', '第二位', '第三位']);
        $draws = Draw::find()->select(['issue', 'numbers', 'draw_time'])->where(['game_id' => $game->id])
            ->orderBy(['draw_time' => SORT_DESC, 'id' => SORT_DESC])->limit(100)->all();
        $rows = [];
        foreach ($draws as $draw) {
            $balls = $draw->getBalls();
            $sum = $racing ? $balls[0] + $balls[1] : array_sum($balls);
            $boundary = $racing ? 12 : ($definition['count'] === 5 ? 23 : 14);
            $rows[] = ['issue' => $draw->issue, 'time' => $draw->draw_time, 'balls' => $balls,
                'sum' => $sum, 'size' => $sum >= $boundary ? '大' : '小', 'parity' => $sum % 2 === 0 ? '双' : '单'];
        }
        $state = CollectionState::findOne((int) $game->id);
        return [
            'game' => ['code' => $code, 'name' => $game->name, 'type' => $definition['type'],
                'positions' => $positions, 'range' => $game->getNumberRange(), 'sum_label' => $racing ? '冠亚和' : '总和',
                'analysis' => $this->analysisOptions($game, $positions, $racing),
                'rule' => $racing ? '按第1至第10名排列；冠亚和为前两名之和，12–19为大，3–11为小。'
                    : ($definition['count'] === 5 ? '按万、千、百、十、个位排列；总和23–45为大，0–22为小。' : '三位号码之和14–27为大，0–13为小。')],
            'list' => $rows,
            'period' => DrawSchedule::estimate(array_column($rows, 'time')),
            'collection' => ['attempt_time' => $state?->attempt_time, 'success_time' => $state?->success_time,
                'unavailable' => $state !== null && $state->error !== ''],
        ];
    }

    /** 提供走势与统计共用的分析项目，大小单双沿用开奖结果的总和分类。 */
    private function analysisOptions(Game $game, array $positions, bool $racing): array
    {
        $sumMin = $racing ? 2 * (int) $game->ball_min + 1 : (int) $game->ball_count * (int) $game->ball_min;
        $sumMax = $racing ? 2 * (int) $game->ball_max - 1 : (int) $game->ball_count * (int) $game->ball_max;
        $options = [
            ['key' => 'sum', 'label' => $racing ? '冠亚和' : '总和', 'field' => 'sum', 'values' => range($sumMin, $sumMax)],
            ['key' => 'size', 'label' => '大小', 'field' => 'size', 'values' => ['大', '小']],
            ['key' => 'parity', 'label' => '单双', 'field' => 'parity', 'values' => ['单', '双']],
        ];
        foreach ($positions as $index => $label) {
            $options[] = ['key' => 'position-' . $index, 'label' => $label, 'field' => 'balls',
                'position' => $index, 'values' => $game->getNumberRange()];
        }
        return $options;
    }
}
