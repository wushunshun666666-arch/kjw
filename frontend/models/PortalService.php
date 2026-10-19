<?php

namespace frontend\models;

use common\models\lottery\Game;
use common\models\lottery\Draw;
use common\models\lottery\Notice;
use yii\web\NotFoundHttpException;

/** 公共门户查询，只公开已发布彩种与公告，不生成虚假开奖。 */
final class PortalService
{
    /** 判断部署是否完成结构初始化，连接异常交由统一错误处理。 */
    public function isInstalled(): bool
    {
        return \Yii::$app->db->schema->getTableSchema('portal_game') !== null;
    }

    /** 准备首页彩种、最新开奖及公告数据。 */
    public function home(): array
    {
        if (!$this->isInstalled()) { return ['installed' => false, 'games' => [], 'notices' => []]; }
        $cards = [];
        $games = Game::find()->where(['enabled' => Game::VISIBLE])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        foreach ($games as $game) {
            $draw = Draw::find()->where(['game_id' => $game->id])->orderBy(['draw_time' => SORT_DESC, 'id' => SORT_DESC])->one();
            $cards[] = ['code' => $game->code, 'name' => $game->name, 'description' => $game->description,
                'draw' => $draw === null ? null : $this->drawData($draw)];
        }
        return ['installed' => true, 'games' => $cards, 'notices' => Notice::find()->where(['enabled' => Notice::VISIBLE])->orderBy(['id' => SORT_DESC])->limit(6)->asArray()->all()];
    }

    /** 准备历史分页和走势，日期筛选使用有界范围索引。 */
    public function history(HistoryForm $form, bool $trend = false): array
    {
        if (!$this->isInstalled()) { return ['installed' => false, 'games' => [], 'selected' => null, 'rows' => [], 'total' => 0, 'pages' => 0, 'columns' => []]; }
        $games = Game::find()->where(['enabled' => Game::VISIBLE])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->all();
        $selected = null;
        foreach ($games as $game) {
            if (($form->game === '' && $selected === null) || $game->code === $form->game) { $selected = $game; }
        }
        if ($selected === null && $form->game !== '') { throw new NotFoundHttpException('彩种不存在或尚未发布。'); }
        if ($selected === null) { return ['installed' => true, 'games' => [], 'selected' => null, 'rows' => [], 'total' => 0, 'pages' => 0, 'columns' => []]; }
        $query = Draw::find()->where(['game_id' => $selected->id]);
        $query->andFilterWhere(['issue' => $form->issue]);
        if ($form->date !== '') {
            $start = new \DateTimeImmutable($form->date, new \DateTimeZone('Asia/Shanghai'));
            $query->andWhere(['>=', 'draw_time', $start->format('Y-m-d 00:00:00')]);
            $query->andWhere(['<', 'draw_time', $start->modify('+1 day')->format('Y-m-d 00:00:00')]);
        }
        $total = (int) (clone $query)->count();
        $size = $trend ? 50 : 30;
        $rows = $query->orderBy(['draw_time' => SORT_DESC, 'id' => SORT_DESC])->offset(((int) $form->page - 1) * $size)->limit($size)->all();
        $maxTotal = (int) $selected->ball_count * (int) $selected->ball_max;
        $minTotal = (int) $selected->ball_count * (int) $selected->ball_min;
        $columns = $maxTotal - $minTotal <= 100 ? range($minTotal, $maxTotal) : [];
        return ['installed' => true, 'games' => $games, 'selected' => $selected,
            'rows' => array_map($this->drawData(...), $rows), 'total' => $total,
            'pages' => (int) ceil($total / $size), 'columns' => $columns];
    }

    /** 取得已经发布的公告。 */
    public function notice(int $id): array
    {
        $notice = Notice::find()->where(['id' => $id, 'enabled' => Notice::VISIBLE])->asArray()->one();
        if ($notice === false || $notice === null) { throw new NotFoundHttpException('公告不存在。'); }
        return $notice;
    }

    /** 将实体转为不含内部管理字段的公开数据。 */
    private function drawData(Draw $draw): array
    {
        return ['issue' => $draw->issue, 'time' => $draw->draw_time, 'balls' => $draw->getBalls(),
            'total' => $draw->getTotal(), 'source' => $draw->source];
    }
}
