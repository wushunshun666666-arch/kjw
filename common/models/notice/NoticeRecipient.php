<?php

namespace common\models\notice;

use common\models\ActiveRecord;
use common\models\system\User;
use yii\db\ActiveQuery;

/**
 * 后台站内消息接收及已读状态实体。
 *
 * @property int $id 接收记录ID
 * @property int $message_id 站内消息ID
 * @property int $user_id 后台接收用户ID
 * @property string|null $read_time 首次阅读时间
 * @property string $create_time 投递时间
 * @property Notice $message 消息内容
 * @property User $user 接收人
 */
final class NoticeRecipient extends ActiveRecord
{
    /** 返回后台站内消息接收表名。 */
    public static function tableName(): string
    {
        return 'notice_recipient';
    }

    /** 返回接收记录字段验证规则。 */
    public function rules(): array
    {
        return [
            [['message_id', 'user_id'], 'required'],
            [['message_id', 'user_id'], 'integer'],
            [['read_time', 'create_time'], 'safe'],
            [['message_id', 'user_id'], 'unique', 'targetAttribute' => ['message_id', 'user_id']],
        ];
    }

    /** 返回接收记录字段中文名称。 */
    public function attributeLabels(): array
    {
        return [
            'id' => '接收记录ID',
            'message_id' => '消息ID',
            'user_id' => '接收人',
            'read_time' => '阅读时间',
            'create_time' => '投递时间',
        ];
    }

    /** 配置接收记录创建时间自动维护行为。 */
    public function behaviors(): array
    {
        $behavior = $this->timestampBehavior();
        $behavior['updatedAtAttribute'] = false;
        return [$behavior];
    }

    /** 判断当前接收记录是否已经阅读。 */
    public function isRead(): bool
    {
        return $this->read_time !== null;
    }

    /** 返回对应站内消息查询。 */
    public function getMessage(): ActiveQuery
    {
        return $this->hasOne(Notice::class, ['id' => 'message_id']);
    }

    /** 返回消息接收后台用户查询。 */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
