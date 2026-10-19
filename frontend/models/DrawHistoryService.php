<?php

namespace frontend\models;

use common\models\lottery\Draw;
use common\models\lottery\FeedCatalog;
use common\models\lottery\Game;
use yii\web\NotFoundHttpException;

/** 按数据库快照分页公开开奖，翻页期间新采集的数据不会挤动已有记录。 */
final class DrawHistoryService
{
    /**
     * 查询已验证的分页输入，保留加拿大28和其他彩种各自的公开行协议。
     *
     * @return array{game: array{code: string}, list: array, pagination: array}
     */
    public function search(DrawHistoryForm $form): array
    {
        $game = Game::findOne(['code' => $form->game, 'enabled' => Game::VISIBLE]);
        if ($game === null || !FeedCatalog::matches($game)) {
            throw new NotFoundHttpException('彩种不存在或尚未发布。');
        }

        $query = Draw::find()->where(['game_id' => $game->id]);
        $snapshot = (int) $form->snapshot;
        if ($snapshot === 0) {
            $snapshot = (int) (clone $query)->max('id');
        }
        $query->andWhere(['<=', 'id', $snapshot]);
        $total = (int) (clone $query)->count();
        $pageSize = (int) $form->page_size;
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $page = min((int) $form->page, $totalPages);
        $draws = $query->select(['game_id', 'issue', 'numbers', 'draw_time'])
            ->orderBy(['draw_time' => SORT_DESC, 'id' => SORT_DESC])
            ->offset(($page - 1) * $pageSize)->limit($pageSize)->all();
        $list = $game->code === 'jnd28'
            ? (new ReferencePortalService())->formatDraws($draws)
            : (new GamePortalService())->formatDraws($draws, $game->code);

        return [
            'game' => ['code' => $game->code],
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => $totalPages,
                'snapshot' => $snapshot,
            ],
        ];
    }
}
