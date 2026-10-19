<?php

namespace common\models\lottery;

use common\models\Error;
use common\models\LogicModel;
use mylibs\yii\db\Transaction;

/** Notice 的创建、编辑与审计事务。 */
final class NoticeModel extends LogicModel
{
    /** 持有当前领域实体。 */
    public function __construct(private Notice $data) { parent::__construct(); }

    /** 按主键恢复领域对象。 */
    public static function getById(int $id): ?self
    {
        $data = Notice::findOne($id);
        return $data === null ? null : new self($data);
    }

    /** 取得只读展示所需实体。 */
    public function getData(): Notice { return $this->data; }

    /** 创建实体并在同一事务内记录操作人。
     * @param array<string, mixed> $attributes 已验证的业务字段
     */
    public static function create(array $attributes): self|Error
    {
        $transaction = new Transaction();
        $entity = new Notice();
        $entity->setAttributes($attributes);
        $entity->version = 0;
        if (!$entity->save()) {
            $transaction->rollback();
            return new Error($entity->getErrors());
        }
        $audit = Audit::record('notice', (int) $entity->id, 'create');
        if (!$audit->save()) {
            $transaction->rollback();
            return new Error($audit->getErrors());
        }
        $transaction->commit();
        return new self($entity);
    }

    /** 修改配置，旧版本提交不得覆盖已更新数据。
     * @param array<string, mixed> $attributes 已验证的业务字段及版本号
     */
    public function modify(array $attributes): bool
    {
        if ((int) $attributes['version'] !== (int) $this->data->version) {
            return $this->setError('数据已被修改，请刷新页面后重试。');
        }
        $transaction = new Transaction();
        unset($attributes['version']);
        $this->data->setAttributes($attributes);
        if (!$this->data->save()) {
            $transaction->rollback();
            return $this->addErrors($this->data->getErrors());
        }
        $audit = Audit::record('notice', (int) $this->data->id, 'modify');
        if (!$audit->save()) {
            $transaction->rollback();
            return $this->addErrors($audit->getErrors());
        }
        $transaction->commit();
        return true;
    }
}
