<?php

/**
 * 客服快捷回复领域逻辑模型。
 *
 * 集中处理快捷回复创建、修改和启停状态迁移，避免应用层直接写实体。
 */

namespace common\models\kefu;

use common\models\Error;
use common\models\LogicModel;

/** 客服快捷回复领域对象。 */
final class KefuQuickReplyModel extends LogicModel
{
    /** @var KefuQuickReply 当前快捷回复实体 */
    private KefuQuickReply $quickReply;

    /** 使用快捷回复实体构造领域对象。 */
    public function __construct(KefuQuickReply $quickReply)
    {
        $this->quickReply = $quickReply;
        parent::__construct();
    }

    /** 按ID恢复快捷回复领域对象。 */
    public static function getById(int $id): ?self
    {
        $quickReply = KefuQuickReply::findOne(['id' => $id]);
        return $quickReply === null ? null : new self($quickReply);
    }

    /** 创建快捷回复。 */
    public static function create(array $attributes): self|Error
    {
        $quickReply = new KefuQuickReply();
        self::assignEditableAttributes($quickReply, $attributes);
        if (!$quickReply->save()) {
            return new Error($quickReply->getErrors());
        }

        return new self($quickReply);
    }

    /** 修改快捷回复内容和排序。 */
    public function modify(array $attributes): bool
    {
        self::assignEditableAttributes($this->quickReply, $attributes);
        if (!$this->quickReply->update(true, ['title', 'content', 'orderby', 'status', 'update_time'])) {
            return $this->addErrors($this->quickReply->getErrors());
        }

        return true;
    }

    /** 启用快捷回复。 */
    public function enable(): bool
    {
        return $this->changeStatus(KefuQuickReply::STATUS_DISABLED, KefuQuickReply::STATUS_ACTIVE);
    }

    /** 停用快捷回复。 */
    public function disable(): bool
    {
        return $this->changeStatus(KefuQuickReply::STATUS_ACTIVE, KefuQuickReply::STATUS_DISABLED);
    }

    /** 返回当前快捷回复实体。 */
    public function getData(): KefuQuickReply
    {
        return $this->quickReply;
    }

    /** 将经过表单验证的可编辑字段赋给实体。 */
    private static function assignEditableAttributes(KefuQuickReply $quickReply, array $attributes): void
    {
        $quickReply->title = trim((string) ($attributes['title'] ?? ''));
        $quickReply->content = trim((string) ($attributes['content'] ?? ''));
        $quickReply->orderby = (int) ($attributes['orderby'] ?? 0);
        $quickReply->status = (int) ($attributes['status'] ?? KefuQuickReply::STATUS_ACTIVE);
    }

    /** 使用旧状态条件原子切换快捷回复状态。 */
    private function changeStatus(int $oldStatus, int $newStatus): bool
    {
        if ((int) $this->quickReply->status !== $oldStatus) {
            return $this->setError('快捷回复当前状态不允许执行该操作。');
        }

        $affected = KefuQuickReply::updateAll(
            ['status' => $newStatus, 'update_time' => KefuQuickReply::expressionNow()],
            ['id' => $this->quickReply->id, 'status' => $oldStatus]
        );
        if ($affected !== 1) {
            return $this->setError('快捷回复状态已经变化，请刷新后重试。');
        }

        $this->quickReply->status = $newStatus;
        return true;
    }
}
