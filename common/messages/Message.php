<?php

namespace common\messages;

use common\helpers\StringHelper;
use common\models\Error;
use common\models\notice\NoticeModel;
use common\models\system\User;

/**
 * 后台站内消息业务基类。
 *
 * 具体业务消息只提供接收人和内容，本类统一完成本地持久化及实时提醒。
 */
abstract class Message
{
    /** 后台运营用户类型。 */
    protected const USER_TYPE_ADMIN = 1000;

    /** @var string|null 最近一次发送失败原因 */
    private ?string $error = null;

    /** 创建站内消息及接收记录，成功入库后触发8700端口实时提醒。 */
    public function send(): bool
    {
        if ((int) $this->getUserType() !== self::USER_TYPE_ADMIN) {
            return $this->setError('站内消息只允许发送给后台运营用户。');
        }
        $result = NoticeModel::create([
            'type' => (int) $this->getTypeId(),
            'title' => (string) $this->getTitle(),
            'content' => (string) $this->getContent(),
            'extras' => (array) $this->getExtras(),
        ], $this->resolveRecipientUserIds($this->getUserId()));
        if ($result instanceof Error) {
            return $this->setError((string) $result->getError());
        }
        return true;
    }

    /** 返回消息接收用户类型。 */
    abstract protected function getUserType();

    /** 返回站内消息类型。 */
    abstract protected function getTypeId();

    /** 返回后台接收用户ID、ID数组，零表示全部有效后台用户。 */
    abstract protected function getUserId();

    /** 返回消息标题。 */
    abstract protected function getTitle();

    /** 返回消息正文。 */
    abstract protected function getContent();

    /** 返回消息业务扩展参数。 */
    protected function getExtras(): array
    {
        return [];
    }

    /** 对订单号进行中间省略以用于消息展示。 */
    protected function ellipsisOrderNumber(string $number): string
    {
        return StringHelper::ellipsisString($number, 4);
    }

    /** 返回最近一次发送失败原因。 */
    public function getError(): ?string
    {
        return $this->error;
    }

    /** 保存发送失败原因并返回false。 */
    protected function setError(string $error): bool
    {
        $this->error = $error;
        return false;
    }

    /**
     * 将业务声明的接收范围归一化为有效后台用户ID列表。
     *
     * @param mixed $userIds 单个用户ID、用户ID数组或零
     * @return int[] 后台接收用户ID
     */
    private function resolveRecipientUserIds($userIds): array
    {
        if (!is_array($userIds) && (int) $userIds === 0) {
            return array_map('intval', User::find()
                ->select('id')
                ->where(['status' => User::STATUS_1000])
                ->orderBy(['id' => SORT_ASC])
                ->column());
        }
        return array_values(array_unique(array_map('intval', is_array($userIds) ? $userIds : [$userIds])));
    }
}
