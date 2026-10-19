<?php

/**
 * 客服聊天消息实体模型。
 *
 * 统一保存会员、客服和系统产生的文字及媒体消息，消息创建后不做物理删除。
 */

namespace common\models\kefu;

use common\models\ActiveRecord;
use common\models\member\Member;
use common\models\system\User;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * 客服聊天消息活动记录模型。
 *
 * @property int $id 消息ID
 * @property int $conversation_id 会话ID
 * @property int $sender_type 发送者类型
 * @property int|null $sender_member_id 会员发送者ID
 * @property int|null $sender_user_id 客服发送者ID
 * @property int $message_type 消息类型
 * @property string|null $content 文字或系统消息内容
 * @property string|null $media_url 媒体安全标识
 * @property string|null $media_mime 真实媒体MIME
 * @property int|null $media_size 媒体字节数
 * @property int|null $media_duration 媒体时长秒数
 * @property int|null $media_width 媒体宽度
 * @property int|null $media_height 媒体高度
 * @property string $client_message_id 客户端幂等编号
 * @property string|null $delivered_time 接收方客户端确认送达时间
 * @property string|null $read_time 接收方读取时间
 * @property string $create_time 创建时间
 * @property-read KefuConversation $conversation 所属会话
 * @property-read Member|null $senderMember 发送会员
 * @property-read User|null $senderUser 发送客服
 */
final class KefuMessage extends ActiveRecord
{
    /** 会员发送者。 */
    public const SENDER_MEMBER = 1000;

    /** 后台客服发送者。 */
    public const SENDER_USER = 2000;

    /** 系统发送者。 */
    public const SENDER_SYSTEM = 9000;

    /** 文字或表情消息。 */
    public const TYPE_TEXT = 1000;

    /** 图片消息。 */
    public const TYPE_IMAGE = 2000;

    /** 语音消息。 */
    public const TYPE_AUDIO = 3000;

    /** 视频消息。 */
    public const TYPE_VIDEO = 4000;

    /** 接待操作等系统消息。 */
    public const TYPE_SYSTEM = 9000;

    /** 返回客服聊天消息表名。 */
    public static function tableName(): string
    {
        return 'kefu_message';
    }

    /** 返回消息字段验证规则。 */
    public function rules(): array
    {
        return [
            [['conversation_id', 'sender_type', 'message_type', 'client_message_id'], 'required'],
            [[
                'conversation_id', 'sender_type', 'sender_member_id', 'sender_user_id', 'message_type',
                'media_size', 'media_duration', 'media_width', 'media_height',
            ], 'integer', 'min' => 0],
            [['content'], 'string', 'max' => 5000],
            [['media_url'], 'string', 'max' => 500],
            [['media_mime'], 'string', 'max' => 100],
            [['client_message_id'], 'string', 'max' => 64],
            [['conversation_id'], 'exist', 'targetClass' => KefuConversation::class, 'targetAttribute' => 'id'],
            [['sender_member_id'], 'exist', 'targetClass' => Member::class, 'targetAttribute' => 'id'],
            [['sender_user_id'], 'exist', 'targetClass' => User::class, 'targetAttribute' => 'id'],
            [['delivered_time', 'read_time', 'create_time'], 'safe'],
            [['sender_type'], 'in', 'range' => array_keys(self::getSenderTypeNames())],
            [['message_type'], 'in', 'range' => array_keys(self::getMessageTypeNames())],
            [
                ['client_message_id'],
                'unique',
                'targetAttribute' => ['conversation_id', 'client_message_id'],
            ],
        ];
    }

    /** 返回客服消息字段中文名称。 */
    public function attributeLabels(): array
    {
        return [
            'id' => '消息ID',
            'conversation_id' => '会话ID',
            'sender_type' => '发送者类型',
            'sender_member_id' => '发送会员ID',
            'sender_user_id' => '发送客服ID',
            'message_type' => '消息类型',
            'content' => '消息内容',
            'media_url' => '媒体资源标识',
            'media_mime' => '媒体MIME',
            'media_size' => '媒体大小',
            'media_duration' => '媒体时长',
            'media_width' => '媒体宽度',
            'media_height' => '媒体高度',
            'client_message_id' => '客户端消息编号',
            'delivered_time' => '送达时间',
            'read_time' => '读取时间',
            'create_time' => '发送时间',
        ];
    }

    /** 仅在新增消息时自动填写发送时间，消息创建后保持不可变。 */
    public function behaviors(): array
    {
        return [[
            'class' => TimestampBehavior::class,
            'createdAtAttribute' => 'create_time',
            'updatedAtAttribute' => false,
            'value' => new Expression('NOW()'),
        ]];
    }

    /** 获取消息所属长期会话。 */
    public function getConversation(): ActiveQuery
    {
        return $this->hasOne(KefuConversation::class, ['id' => 'conversation_id']);
    }

    /** 获取发送消息的会员。 */
    public function getSenderMember(): ActiveQuery
    {
        return $this->hasOne(Member::class, ['id' => 'sender_member_id']);
    }

    /** 获取发送消息的后台客服。 */
    public function getSenderUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'sender_user_id']);
    }

    /** 返回发送者类型字典。 */
    public static function getSenderTypeNames(): array
    {
        return [
            self::SENDER_MEMBER => '会员',
            self::SENDER_USER => '客服',
            self::SENDER_SYSTEM => '系统',
        ];
    }

    /** 返回消息类型字典。 */
    public static function getMessageTypeNames(): array
    {
        return [
            self::TYPE_TEXT => '文字',
            self::TYPE_IMAGE => '图片',
            self::TYPE_AUDIO => '语音',
            self::TYPE_VIDEO => '视频',
            self::TYPE_SYSTEM => '系统消息',
        ];
    }

    /** 返回适合左侧客户列表显示的消息摘要。 */
    public function getPreview(): string
    {
        if ($this->message_type === self::TYPE_TEXT || $this->message_type === self::TYPE_SYSTEM) {
            return mb_substr(trim((string) $this->content), 0, 255);
        }

        return '[' . (self::getMessageTypeNames()[$this->message_type] ?? '消息') . ']';
    }
}
