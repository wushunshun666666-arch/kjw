<?php

/**
 * 客服快捷回复应用服务。
 *
 * 负责快捷回复查询、创建、修改及启停状态切换。
 */

namespace app\models\kefu;

use app\models\Service;
use common\models\Error;
use common\models\kefu\KefuQuickReply;
use common\models\kefu\KefuQuickReplyModel;
use yii\data\ActiveDataProvider;

/** 客服快捷回复后台服务。 */
final class QuickReplyService extends Service
{
    /**
     * 根据搜索表单构造快捷回复列表。
     *
     * @return ActiveDataProvider 快捷回复列表数据提供器
     */
    public function search(QuickReplySearchForm $form)
    {
        $query = KefuQuickReply::find();
        $query->andFilterWhere(['kefu_quick_reply.status' => $form->status]);
        $keyword = trim((string) $form->keyword);
        if ($keyword !== '') {
            $query->andWhere([
                'or',
                ['like', 'kefu_quick_reply.title', $keyword],
                ['like', 'kefu_quick_reply.content', $keyword],
            ]);
        }
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $this->getOption('pagesize')],
            'sort' => [
                'attributes' => [
                    'kefu_quick_reply.id',
                    'kefu_quick_reply.title' => [
                        'asc' => ['CONVERT(kefu_quick_reply.title using gbk)' => SORT_ASC],
                        'desc' => ['CONVERT(kefu_quick_reply.title using gbk)' => SORT_DESC],
                    ],
                    'kefu_quick_reply.orderby',
                    'kefu_quick_reply.status',
                    'kefu_quick_reply.create_time' => [
                        'asc' => ['kefu_quick_reply.id' => SORT_ASC],
                        'desc' => ['kefu_quick_reply.id' => SORT_DESC],
                    ],
                ],
                'defaultOrder' => [
                    'kefu_quick_reply.orderby' => SORT_ASC,
                    'kefu_quick_reply.id' => SORT_DESC,
                ],
            ],
        ]);
    }

    /** 新增公共快捷回复。 */
    public function add(QuickReplyForm $form): bool
    {
        $result = KefuQuickReplyModel::create($form->getSafeAttributes());
        if ($result instanceof Error) {
            return $this->addErrors($result->getErrors());
        }

        return true;
    }

    /** 修改公共快捷回复。 */
    public function modify(int $id, QuickReplyForm $form): bool
    {
        $model = $this->getModel($id);
        if ($model === false) {
            return false;
        }
        if (!$model->modify($form->getSafeAttributes())) {
            return $this->addErrors($model->getErrors());
        }

        return true;
    }

    /** 获取快捷回复详情。 */
    public function getById(int $id): KefuQuickReply|false
    {
        $model = $this->getModel($id);
        if ($model === false) {
            return false;
        }
        return $model->getData();
    }

    /** 启用快捷回复。 */
    public function enable(int $id): bool
    {
        return $this->executeStatusAction($id, 'enable');
    }

    /** 停用快捷回复。 */
    public function disable(int $id): bool
    {
        return $this->executeStatusAction($id, 'disable');
    }

    /** 返回快捷回复状态字典。 */
    public function getStatusNames(): array
    {
        return KefuQuickReply::getStatusNames();
    }

    /** 获取快捷回复领域对象并统一处理不存在错误。 */
    private function getModel(int $id): KefuQuickReplyModel|false
    {
        $model = KefuQuickReplyModel::getById($id);
        if ($model === null) {
            $this->setError('快捷回复不存在。');
            return false;
        }

        return $model;
    }

    /** 执行快捷回复状态动作并复制领域错误。 */
    private function executeStatusAction(int $id, string $action): bool
    {
        $model = $this->getModel($id);
        if ($model === false) {
            return false;
        }

        $success = $action === 'enable' ? $model->enable() : $model->disable();
        return $success ?: $this->addErrors($model->getErrors());
    }
}
