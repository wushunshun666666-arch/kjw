<?php

namespace common\models\notice;

use common\models\ActiveRecord;
use common\models\system\User;
use yii\db\ActiveQuery;

/**
 * 后台站内消息实体。
 *
 * @property int $id 站内消息ID
 * @property int $type 消息类型
 * @property string $title 消息标题
 * @property string $content 消息内容
 * @property string|null $extras 业务扩展参数JSON
 * @property int|null $create_user_id 创建消息的后台用户ID
 * @property string $create_time 创建时间
 * @property NoticeRecipient[] $recipients 接收记录
 * @property User|null $creator 创建人
 */
final class Notice extends ActiveRecord
{
    /** 普通运营通知。 */
    public const TYPE_NOTICE = 1000;

    /** 上下分业务通知。 */
    public const TYPE_ACCOUNT_CHANGE = 1100;

    /** 系统运行告警。 */
    public const TYPE_SYSTEM_ALERT = 1200;

    /** 订单或库存不足通知。 */
    public const TYPE_INVENTORY_LOW = 1300;

    /** 返回后台站内消息表名。 */
    public static function tableName(): string
    {
        return 'notice';
    }

    /** 返回消息字段验证规则。 */
    public function rules(): array
    {
        return [
            [['type', 'title', 'content'], 'required'],
            [['type', 'create_user_id'], 'integer'],
            [['extras'], 'string'],
            [['title'], 'string', 'max' => 100],
            [['content'], 'string', 'max' => 1000],
            [['type'], 'in', 'range' => array_keys(self::getTypeNames())],
            [['create_time'], 'safe'],
        ];
    }

    /** 返回消息字段中文名称。 */
    public function attributeLabels(): array
    {
        return [
            'id' => '消息ID',
            'type' => '消息类型',
            'title' => '消息标题',
            'content' => '消息内容',
            'extras' => '扩展参数',
            'create_user_id' => '创建人',
            'create_time' => '创建时间',
        ];
    }

    /** 配置消息创建时间自动维护行为。 */
    public function behaviors(): array
    {
        $behavior = $this->timestampBehavior();
        $behavior['updatedAtAttribute'] = false;
        return [$behavior];
    }

    /** 返回消息类型字典。 */
    public static function getTypeNames(): array
    {
        return [
            self::TYPE_NOTICE => '普通通知',
            self::TYPE_ACCOUNT_CHANGE => '上下分通知',
            self::TYPE_SYSTEM_ALERT => '系统告警',
            self::TYPE_INVENTORY_LOW => '库存不足',
        ];
    }

    /** 返回消息类型名称。 */
    public function getTypeName(): string
    {
        return self::getTypeNames()[(int) $this->type] ?? '未知';
    }

    /** 返回经过安全解析的业务扩展参数。 */
    public function getExtrasData(): array
    {
        if ($this->extras === null || $this->extras === '') {
            return [];
        }
        $data = json_decode($this->extras, true);
        return is_array($data) ? $data : [];
    }

    /** 返回消息的接收记录查询。 */
    public function getRecipients(): ActiveQuery
    {
        return $this->hasMany(NoticeRecipient::class, ['message_id' => 'id']);
    }

    /** 返回创建消息的后台用户查询。 */
    public function getCreator(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'create_user_id']);
    }
}
