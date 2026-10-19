<?php

/**
 * 客服长期会话实体模型。
 *
 * 每个会员只有一条会话，实体负责保存当前接待客服、未读数和最后消息摘要。
 */

namespace common\models\kefu;

use common\models\ActiveRecord;
use common\models\member\Member;
use common\models\system\User;
use yii\db\ActiveQuery;

/**
 * 客服长期会话活动记录模型。
 *
 * @property int $id 客服会话ID
 * @property int $member_id 会员ID
 * @property int|null $user_id 当前接待客服ID
 * @property int $status 会话状态
 * @property int|null $last_message_id 最后消息ID
 * @property int|null $last_message_type 最后消息类型
 * @property string $last_message_preview 最后消息摘要
 * @property string|null $last_message_time 最近聊天时间
 * @property int $member_unread_count 会员未读数
 * @property int $user_unread_count 客服未读数
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property-read Member $member 会话会员
 * @property-read User|null $user 当前接待客服
 * @property-read KefuMessage[] $messages 全部聊天消息
 */
final class KefuConversation extends ActiveRecord
{
    /** 等待客服领取。 */
    public const STATUS_WAITING = 1000;

    /** 客服接待中。 */
    public const STATUS_SERVING = 1100;

    /** 本次接待已结束。 */
    public const STATUS_ENDED = 9000;

    /** 返回客服长期会话表名。 */
    public static function tableName(): string
    {
        return 'kefu_conversation';
    }

    /** 返回会话字段验证规则。 */
    public function rules(): array
    {
        return [
            [['member_id', 'status'], 'required'],
            [['member_id', 'user_id', 'status', 'last_message_id', 'last_message_type'], 'integer'],
            [['member_unread_count', 'user_unread_count'], 'integer', 'min' => 0],
            [['member_id'], 'unique'],
            [['member_id'], 'exist', 'targetClass' => Member::class, 'targetAttribute' => 'id'],
            [['user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            [['last_message_id'], 'exist', 'targetClass' => KefuMessage::class, 'targetAttribute' => 'id'],
            [['last_message_preview'], 'string', 'max' => 255],
            [['last_message_time', 'create_time', 'update_time'], 'safe'],
            [['status'], 'in', 'range' => array_keys(self::getStatusNames())],
        ];
    }

    /** 返回会话字段中文名称。 */
    public function attributeLabels(): array
    {
        return [
            'id' => '会话ID',
            'member_id' => '会员ID',
            'user_id' => '当前客服',
            'status' => '会话状态',
            'last_message_id' => '最后消息ID',
            'last_message_type' => '最后消息类型',
            'last_message_preview' => '最后消息摘要',
            'last_message_time' => '最近聊天时间',
            'member_unread_count' => '会员未读数',
            'user_unread_count' => '客服未读数',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
        ];
    }

    /** 配置创建时间和更新时间自动维护行为。 */
    public function behaviors(): array
    {
        return [$this->timestampBehavior()];
    }

    /** 获取会话所属会员。 */
    public function getMember(): ActiveQuery
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }

    /** 获取当前接待客服。 */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    /** 获取会话全部聊天消息。 */
    public function getMessages(): ActiveQuery
    {
        return $this->hasMany(KefuMessage::class, ['conversation_id' => 'id']);
    }

    /** 返回会话状态字典。 */
    public static function getStatusNames(): array
    {
        return [
            self::STATUS_WAITING => '等待接待',
            self::STATUS_SERVING => '接待中',
            self::STATUS_ENDED => '已结束',
        ];
    }

    /** 返回当前会话状态名称。 */
    public function getStatusName(): string
    {
        return self::getStatusNames()[$this->status] ?? '未知';
    }
}
