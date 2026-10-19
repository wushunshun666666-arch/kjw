<?php

namespace app\models\message;

use app\models\Service;
use common\models\notice\Notice;
use common\models\notice\NoticeModel;
use common\models\notice\NoticeRecipient;
use yii\data\ActiveDataProvider;

/** 后台站内消息查询及已读操作应用服务。 */
final class MessageService extends Service
{
    /** 仅查看未读消息的搜索条件。 */
    public const CONDITION_UNREAD = 'unread';

    /** 返回消息列表筛选字典。 */
    public function getConditionGroup(): array
    {
        return [self::CONDITION_UNREAD => '未读消息'] + Notice::getTypeNames();
    }

    /**
     * 根据搜索表单构造当前后台用户的消息列表。
     *
     * @return ActiveDataProvider 站内消息接收记录数据提供器
     */
    public function search(SearchForm $form)
    {
        $query = NoticeRecipient::find()
            ->alias('recipient')
            ->with('message')
            ->innerJoinWith(['message message'])
            ->where(['recipient.user_id' => $this->getUserId()]);
        if ((string) $form->condition === self::CONDITION_UNREAD) {
            $query->andWhere(['recipient.read_time' => null]);
        } elseif ($form->condition !== null && $form->condition !== '') {
            $query->andWhere(['message.type' => (int) $form->condition]);
        }
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $this->getOption('pagesize')],
            'sort' => [
                'attributes' => ['recipient.message_id', 'recipient.read_time', 'message.type', 'message.create_time'],
                'defaultOrder' => ['recipient.message_id' => SORT_DESC],
            ],
        ]);
    }

    /** 返回顶部消息菜单最近十条消息及未读数量。 */
    public function getMenuList(): array
    {
        $recipients = NoticeRecipient::find()
            ->with('message')
            ->where(['user_id' => $this->getUserId()])
            ->orderBy(['message_id' => SORT_DESC])
            ->limit(10)
            ->all();
        $items = [];
        foreach ($recipients as $recipient) {
            if ($recipient->message !== null) {
                $items[] = $this->serializeRecipient($recipient);
            }
        }
        return ['items' => $items, 'nums' => $this->getNewNums()];
    }

    /** 返回当前后台用户的未读站内消息数量。 */
    public function getNewNums(): int
    {
        return (int) NoticeRecipient::find()
            ->where(['user_id' => $this->getUserId(), 'read_time' => null])
            ->count();
    }

    /** 标记当前用户的一条站内消息为已读并返回最新未读数。 */
    public function read(ReadForm $form): array|false
    {
        $model = NoticeModel::getById((int) $form->id);
        if ($model === null) {
            return $this->setError('站内消息不存在。');
        }
        if (!$model->markRead($this->getUserId())) {
            return $this->setError((string) $model->getError());
        }
        return ['nums' => $this->getNewNums()];
    }

    /** 将当前后台用户的全部站内消息标记为已读。 */
    public function markAllRead(): void
    {
        NoticeModel::markAllRead($this->getUserId());
    }

    /** 将消息接收实体转换为前端菜单所需的只读数据。 */
    private function serializeRecipient(NoticeRecipient $recipient): array
    {
        $message = $recipient->message;
        return [
            'id' => (int) $message->id,
            'typeId' => (int) $message->type,
            'title' => (string) $message->title,
            'content' => (string) $message->content,
            'extras' => $message->getExtrasData(),
            'isRead' => $recipient->isRead(),
            'createTime' => (string) $message->create_time,
        ];
    }
}
