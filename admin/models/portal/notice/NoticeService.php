<?php

namespace app\models\portal\notice;

use common\models\lottery\Notice;
use common\models\lottery\NoticeModel;
use common\models\Error;
use yii\data\ActiveDataProvider;

/** 后台公告查询与写入用例。 */
final class NoticeService extends \app\models\Service
{
    /** 按已验证的条件查询。
     * @return ActiveDataProvider 分页结果
     */
    public function search(SearchForm $form)
    {
        $query = Notice::find();
        $query->andFilterWhere(['like', 'title', $form->title]);
        $query->andFilterWhere(['enabled' => $form->enabled]);
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $this->getOption('pagesize')],
            'sort' => ['attributes' => ['id', 'title', 'enabled'], 'defaultOrder' => ['id' => SORT_DESC]],
        ]);
    }

    /** 取得详情，记录不存在时返回空。 */
    public function getDetail(int $id): ?Notice { return Notice::findOne($id); }

    /** 返回发布状态。 */
    public function getEnabledNames(): array { return Notice::getEnabledNames(); }

    /** 新增公告，领域错误转换为用例错误。 */
    public function add(AddForm $form): bool
    {
        $result = NoticeModel::create($form->getSafeAttributes());
        return Error::isError($result) ? $this->addErrors($result->getErrors()) : true;
    }

    /** 修改公告并防止版本覆盖。 */
    public function modify(int $id, ModifyForm $form): bool
    {
        $model = NoticeModel::getById($id);
        if ($model === null) { return $this->setError('记录不存在。'); }
        return $model->modify($form->getSafeAttributes()) ? true : $this->addErrors($model->getErrors());
    }
}
