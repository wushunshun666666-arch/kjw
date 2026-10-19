<?php

/**
 * 客服快捷回复实体模型。
 *
 * 快捷回复由全平台客服共用，不关联特定后台用户。
 */

namespace common\models\kefu;

use common\models\ActiveRecord;

/**
 * 客服快捷回复活动记录模型。
 *
 * @property int $id 快捷回复ID
 * @property string $title 短语标题
 * @property string $content 回复内容
 * @property int $orderby 排序值
 * @property int $status 状态
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
final class KefuQuickReply extends ActiveRecord
{
    /** 启用状态。 */
    public const STATUS_ACTIVE = 1000;

    /** 停用状态。 */
    public const STATUS_DISABLED = 9000;

    /** 返回客服快捷回复表名。 */
    public static function tableName(): string
    {
        return 'kefu_quick_reply';
    }

    /** 返回快捷回复字段验证规则。 */
    public function rules(): array
    {
        return [
            [['title', 'content', 'status'], 'required'],
            [['orderby', 'status'], 'integer'],
            [['title'], 'string', 'max' => 60],
            [['content'], 'string', 'max' => 5000],
            [['status'], 'in', 'range' => array_keys(self::getStatusNames())],
            [['create_time', 'update_time'], 'safe'],
        ];
    }

    /** 返回快捷回复字段中文名称。 */
    public function attributeLabels(): array
    {
        return [
            'id' => '快捷回复ID',
            'title' => '短语标题',
            'content' => '回复内容',
            'orderby' => '排序值',
            'status' => '状态',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }

    /** 配置创建时间和更新时间自动维护行为。 */
    public function behaviors(): array
    {
        return [$this->timestampBehavior()];
    }

    /** 返回快捷回复状态字典。 */
    public static function getStatusNames(): array
    {
        return [
            self::STATUS_ACTIVE => '启用',
            self::STATUS_DISABLED => '停用',
        ];
    }

    /** 返回当前快捷回复状态名称。 */
    public function getStatusName(): string
    {
        return self::getStatusNames()[$this->status] ?? '未知';
    }

    /** 判断快捷回复当前是否启用。 */
    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }
}
