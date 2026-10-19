<?php

/**
 * 会员账户实体模型。
 *
 * 每个会员必须且只能拥有一个账户，用于保存后台需要的虚拟数量数据。
 */

namespace common\models\member;

use common\models\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * 会员账户活动记录模型。
 *
 * @property int $id 账户ID
 * @property int $member_id 会员ID
 * @property string $available_amount 可用数量
 * @property string $frozen_amount 冻结数量
 * @property int $row_version 并发版本号
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property-read Member $member 账户所属会员
 */
final class Account extends ActiveRecord
{
    /**
     * 返回会员账户表名。
     */
    public static function tableName(): string
    {
        return 'account';
    }

    /**
     * 返回账户字段验证规则。
     */
    public function rules(): array
    {
        return [
            [['member_id'], 'required'],
            [['member_id', 'row_version'], 'integer', 'min' => 0],
            [['member_id'], 'unique'],
            [['member_id'], 'exist', 'targetClass' => Member::class, 'targetAttribute' => 'id'],
            [['available_amount', 'frozen_amount'], 'number', 'min' => 0],
            [['create_time', 'update_time'], 'safe'],
        ];
    }

    /**
     * 返回账户字段的中文名称。
     */
    public function attributeLabels(): array
    {
        return [
            'id' => '账户ID',
            'member_id' => '会员ID',
            'available_amount' => '可用数量',
            'frozen_amount' => '冻结数量',
            'row_version' => '并发版本',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }

    /**
     * 配置创建时间和更新时间自动维护行为。
     */
    public function behaviors(): array
    {
        return [$this->timestampBehavior()];
    }

    /**
     * 获取账户所属会员。
     */
    public function getMember(): ActiveQuery
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }
}
