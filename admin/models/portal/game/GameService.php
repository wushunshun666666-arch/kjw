<?php

namespace app\models\portal\game;

use common\models\lottery\Game;
use common\models\lottery\GameModel;
use common\models\Error;
use yii\data\ActiveDataProvider;

/** 后台彩种查询与写入用例。 */
final class GameService extends \app\models\Service
{
    /** 按已验证的条件查询。
     * @return ActiveDataProvider 分页结果
     */
    public function search(SearchForm $form)
    {
        $query = Game::find();
        $query->andFilterWhere(['like', 'name', $form->name]);
        $query->andFilterWhere(['enabled' => $form->enabled]);
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $this->getOption('pagesize')],
            'sort' => ['attributes' => ['id', 'name', 'enabled'], 'defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }

    /** 取得详情，记录不存在时返回空。 */
    public function getDetail(int $id): ?Game { return Game::findOne($id); }

    /** 返回发布状态。 */
    public function getEnabledNames(): array { return Game::getEnabledNames(); }

    /** 新增彩种，领域错误转换为用例错误。 */
    public function add(AddForm $form): bool
    {
        $result = GameModel::create($form->getSafeAttributes());
        return Error::isError($result) ? $this->addErrors($result->getErrors()) : true;
    }

    /** 修改彩种并防止版本覆盖。 */
    public function modify(int $id, ModifyForm $form): bool
    {
        $model = GameModel::getById($id);
        if ($model === null) { return $this->setError('记录不存在。'); }
        return $model->modify($form->getSafeAttributes()) ? true : $this->addErrors($model->getErrors());
    }
}
