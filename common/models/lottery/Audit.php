<?php

namespace common\models\lottery;

/** 开奖业务的只追加操作审计。
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property string $action
 * @property int $operator_id
 * @property string $create_time
 */
final class Audit extends \common\models\ActiveRecord
{
    /** 返回审计表名。 */
    public static function tableName(): string { return 'portal_audit'; }

    /** 验证审计主键和操作信息。 */
    public function rules(): array
    {
        return [
            [['entity_type', 'entity_id', 'action', 'operator_id', 'create_time'], 'required'],
            [['entity_id', 'operator_id'], 'integer'],
            [['entity_type', 'action'], 'string', 'max' => 32],
            [['create_time'], 'safe'],
        ];
    }

    /** 使用当前可信身份创建审计实体，持久化由领域事务负责。 */
    public static function record(string $type, int $id, string $action): self
    {
        return new self([
            'entity_type' => $type, 'entity_id' => $id, 'action' => $action,
            'operator_id' => (int) \common\models\LogicModel::getSysUserId(),
            'create_time' => (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d H:i:s'),
        ]);
    }
}
