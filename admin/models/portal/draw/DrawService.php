<?php

namespace app\models\portal\draw;

use common\models\lottery\Draw;
use common\models\lottery\DrawInput;
use common\models\lottery\DrawModel;
use common\models\lottery\Game;
use common\models\Error;
use yii\data\ActiveDataProvider;

/** 管理端开奖记录查询与标准接收用例。 */
final class DrawService extends \app\models\Service
{
    /** 执行一次来源采集并返回统计。 */
    public function collect(string $code = 'jnd28'): array|false
    {
        $model = new \common\models\lottery\CollectionModel();
        $result = $model->collect($code);
        return $result === false ? $this->addErrors($model->getErrors()) : $result;
    }

    /** 为采集面板准备来源、状态与最新开奖时间，不依赖前台访问触发采集。 */
    public function getCollectionSources(): array
    {
        $games = Game::find()->where(['code' => array_keys(\common\models\lottery\FeedCatalog::GAMES)])->orderBy(['sort_order' => SORT_ASC])->all();
        $states = \common\models\lottery\CollectionState::find()->indexBy('game_id')->all();
        $latest = Draw::find()->select(['game_id', 'latest' => new \yii\db\Expression('MAX(draw_time)')])->groupBy('game_id')->asArray()->indexBy('game_id')->all();
        return array_map(static fn (Game $game): array => [
            'code' => $game->code, 'name' => $game->name,
            'url' => \common\models\lottery\FeedCatalog::url($game->code),
            'attempt' => $states[$game->id]->attempt_time ?? '尚未采集',
            'success' => $states[$game->id]->success_time ?? '暂无',
            'status' => isset($states[$game->id]) ? ($states[$game->id]->error ?: '采集正常') : '尚未采集',
            'latest' => $latest[$game->id]['latest'] ?? '暂无开奖',
        ], $games);
    }

    /** 按期号和彩种查询，展示关联彩种名称。
     * @return ActiveDataProvider 开奖记录分页数据
     */
    public function search(SearchForm $form)
    {
        $query = Draw::find()->with('game');
        $query->andFilterWhere(['game_id' => $form->game_id]);
        $query->andFilterWhere(['issue' => $form->issue]);
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $this->getOption('pagesize')],
            'sort' => ['attributes' => ['id', 'draw_time', 'issue'], 'defaultOrder' => ['draw_time' => SORT_DESC, 'id' => SORT_DESC]],
        ]);
    }

    /** 返回有限规模的彩种选择项。 */
    public function getGameNames(): array
    {
        return Game::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])->select('name')->indexBy('id')->column();
    }

    /** 调用统一入库领域动作。 */
    public function receive(DrawInput $form): bool
    {
        $result = DrawModel::receive($form);
        if (Error::isError($result)) { return $this->addErrors($result->getErrors()); }
        $prediction = \common\models\lottery\PredictionModel::generateNext((int) $result->game_id);
        return Error::isError($prediction) ? $this->addErrors($prediction->getErrors()) : true;
    }
}
