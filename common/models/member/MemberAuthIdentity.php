<?php

/**
 * 会员社交登录身份实体模型。
 *
 * 一个会员可以绑定多个社交平台身份，同一平台身份只能归属一个会员。
 */

namespace common\models\member;

use common\models\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * 会员社交登录身份活动记录模型。
 *
 * @property int $id 社交身份ID
 * @property int $member_id 会员ID
 * @property int $provider 社交平台编码
 * @property string $provider_app_id 社交平台应用ID
 * @property string $provider_user_id 社交平台稳定用户ID
 * @property string|null $union_id 社交平台跨应用统一ID
 * @property string $nickname 社交平台昵称快照
 * @property string $avatar 社交平台头像地址快照
 * @property int $status 绑定状态
 * @property string|null $last_login_time 最近登录时间
 * @property string $create_time 首次绑定时间
 * @property string $update_time 更新时间
 * @property-read Member $member 身份所属会员
 */
final class MemberAuthIdentity extends ActiveRecord
{
    /** QQ平台。 */
    public const PROVIDER_QQ = 2000;

    /** 微信平台。 */
    public const PROVIDER_WECHAT = 2100;

    /** 支付宝平台。 */
    public const PROVIDER_ALIPAY = 2200;

    /** Facebook平台。 */
    public const PROVIDER_FACEBOOK = 3000;

    /** X平台。 */
    public const PROVIDER_X = 3100;

    /** Telegram平台。 */
    public const PROVIDER_TELEGRAM = 3200;

    /** 抖音平台。 */
    public const PROVIDER_DOUYIN = 4000;

    /** 快手平台。 */
    public const PROVIDER_KUAISHOU = 4100;

    /** 微博平台。 */
    public const PROVIDER_WEIBO = 4200;

    /** 当前处于绑定状态。 */
    public const STATUS_BOUND = 1000;

    /** 已解除绑定。 */
    public const STATUS_UNBOUND = 9000;

    /**
     * 返回社交登录身份表名。
     */
    public static function tableName(): string
    {
        return 'member_auth_identity';
    }

    /**
     * 返回社交身份字段验证规则。
     */
    public function rules(): array
    {
        return [
            [['member_id', 'provider', 'provider_app_id', 'provider_user_id', 'status'], 'required'],
            [['member_id', 'provider', 'status'], 'integer'],
            [['member_id'], 'exist', 'targetClass' => Member::class, 'targetAttribute' => 'id'],
            [['provider_app_id', 'nickname'], 'string', 'max' => 100],
            [['provider_user_id', 'union_id'], 'string', 'max' => 191],
            [['avatar'], 'string', 'max' => 500],
            [['avatar'], 'url', 'validSchemes' => ['http', 'https']],
            [['last_login_time', 'create_time', 'update_time'], 'safe'],
            [['provider'], 'in', 'range' => array_keys(self::getProviderNames())],
            [['status'], 'in', 'range' => array_keys(self::getStatusNames())],
            [
                ['provider', 'provider_app_id', 'provider_user_id'],
                'unique',
                'targetAttribute' => ['provider', 'provider_app_id', 'provider_user_id'],
            ],
            [
                ['member_id', 'provider', 'provider_app_id'],
                'unique',
                'targetAttribute' => ['member_id', 'provider', 'provider_app_id'],
            ],
        ];
    }

    /**
     * 返回社交身份字段的中文名称。
     */
    public function attributeLabels(): array
    {
        return [
            'id' => '身份ID',
            'member_id' => '会员ID',
            'provider' => '社交平台',
            'provider_app_id' => '平台应用ID',
            'provider_user_id' => '平台用户ID',
            'union_id' => '统一用户ID',
            'nickname' => '平台昵称',
            'avatar' => '平台头像',
            'status' => '绑定状态',
            'last_login_time' => '最后登录时间',
            'create_time' => '绑定时间',
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
     * 获取社交身份所属会员。
     */
    public function getMember(): ActiveQuery
    {
        return $this->hasOne(Member::class, ['id' => 'member_id']);
    }

    /**
     * 返回支持的社交平台字典。
     */
    public static function getProviderNames(): array
    {
        return [
            self::PROVIDER_QQ => 'QQ',
            self::PROVIDER_WECHAT => '微信',
            self::PROVIDER_ALIPAY => '支付宝',
            self::PROVIDER_FACEBOOK => 'Facebook',
            self::PROVIDER_X => 'X',
            self::PROVIDER_TELEGRAM => 'Telegram',
            self::PROVIDER_DOUYIN => '抖音',
            self::PROVIDER_KUAISHOU => '快手',
            self::PROVIDER_WEIBO => '微博',
        ];
    }

    /**
     * 返回当前社交平台名称。
     */
    public function getProviderName(): string
    {
        return self::getProviderNames()[$this->provider] ?? '未知';
    }

    /**
     * 返回社交身份绑定状态字典。
     */
    public static function getStatusNames(): array
    {
        return [
            self::STATUS_BOUND => '已绑定',
            self::STATUS_UNBOUND => '已解绑',
        ];
    }

    /**
     * 返回当前绑定状态名称。
     */
    public function getStatusName(): string
    {
        return self::getStatusNames()[$this->status] ?? '未知';
    }
}
