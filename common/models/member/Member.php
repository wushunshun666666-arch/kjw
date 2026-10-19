<?php

/**
 * 会员实体模型。
 *
 * 负责会员基础资料、推广上下级关系、登录状态和关联实体的持久化映射。
 */

namespace common\models\member;

use common\models\ActiveRecord;
use Yii;
use yii\db\ActiveQuery;

/**
 * 会员活动记录模型。
 *
 * @property int $id 会员ID
 * @property int|null $parent_id 直属推广会员ID
 * @property string $invite_code 唯一推广码
 * @property string|null $username 备用登录用户名
 * @property string|null $password_hash 备用登录密码哈希
 * @property string $nickname 会员昵称
 * @property string $avatar 会员头像地址
 * @property string|null $mobile 已验证手机号码
 * @property string|null $email 已验证邮箱
 * @property int $status 会员状态
 * @property int $register_type 注册方式
 * @property int $auth_version 认证版本号
 * @property string|null $last_login_time 最近登录时间
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 * @property-read Account|null $account 会员账户
 * @property-read MemberAuthIdentity[] $authIdentities 社交登录身份集合
 * @property-read Member|null $parent 直属上级会员
 * @property-read Member[] $children 直属下级会员集合
 */
final class Member extends ActiveRecord
{
    /** 正常状态。 */
    public const STATUS_ACTIVE = 1000;

    /** 冻结状态。 */
    public const STATUS_FROZEN = 1100;

    /** 用户名注册。 */
    public const REGISTER_USERNAME = 1000;

    /** QQ注册。 */
    public const REGISTER_QQ = 2000;

    /** 微信注册。 */
    public const REGISTER_WECHAT = 2100;

    /** 支付宝注册。 */
    public const REGISTER_ALIPAY = 2200;

    /** Facebook注册。 */
    public const REGISTER_FACEBOOK = 3000;

    /** X平台注册。 */
    public const REGISTER_X = 3100;

    /** Telegram注册。 */
    public const REGISTER_TELEGRAM = 3200;

    /** 抖音注册。 */
    public const REGISTER_DOUYIN = 4000;

    /** 快手注册。 */
    public const REGISTER_KUAISHOU = 4100;

    /** 微博注册。 */
    public const REGISTER_WEIBO = 4200;

    /** 后台人工创建。 */
    public const REGISTER_ADMIN = 9000;

    /**
     * 返回会员表名。
     */
    public static function tableName(): string
    {
        return 'member';
    }

    /**
     * 返回会员字段验证规则。
     */
    public function rules(): array
    {
        return [
            [['invite_code', 'nickname', 'status', 'register_type', 'auth_version'], 'required'],
            [['parent_id', 'status', 'register_type', 'auth_version'], 'integer'],
            [['parent_id'], 'exist', 'targetClass' => self::class, 'targetAttribute' => 'id'],
            [['invite_code'], 'string', 'length' => 10],
            [['invite_code'], 'match', 'pattern' => '/^[A-Z0-9]+$/'],
            [['invite_code'], 'unique'],
            [['last_login_time', 'create_time', 'update_time'], 'safe'],
            [['username'], 'string', 'max' => 40],
            [['username'], 'match', 'pattern' => '/^[a-zA-Z][a-zA-Z0-9_-]*$/'],
            [['username'], 'unique'],
            [['password_hash'], 'string', 'max' => 255],
            [['nickname'], 'string', 'max' => 60],
            [['avatar'], 'string', 'max' => 500],
            [['avatar'], 'url', 'validSchemes' => ['http', 'https']],
            [['mobile'], 'string', 'max' => 32],
            [['mobile'], 'match', 'pattern' => '/^\+[1-9]\d{6,14}$/'],
            [['mobile'], 'unique'],
            [['email'], 'string', 'max' => 191],
            [['email'], 'email'],
            [['email'], 'unique'],
            [['status'], 'in', 'range' => array_keys(self::getStatusNames())],
            [['register_type'], 'in', 'range' => array_keys(self::getRegisterTypeNames())],
        ];
    }

    /**
     * 返回会员字段的中文名称。
     */
    public function attributeLabels(): array
    {
        return [
            'id' => '会员ID',
            'parent_id' => '直属上级ID',
            'invite_code' => '推广码',
            'username' => '用户名',
            'password_hash' => '登录密码',
            'nickname' => '昵称',
            'avatar' => '头像地址',
            'mobile' => '手机号码',
            'email' => 'Email',
            'status' => '状态',
            'register_type' => '注册方式',
            'auth_version' => '认证版本',
            'last_login_time' => '最后登录时间',
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
     * 获取会员的一对一账户。
     */
    public function getAccount(): ActiveQuery
    {
        return $this->hasOne(Account::class, ['member_id' => 'id']);
    }

    /**
     * 获取会员绑定的全部社交身份。
     */
    public function getAuthIdentities(): ActiveQuery
    {
        return $this->hasMany(MemberAuthIdentity::class, ['member_id' => 'id']);
    }

    /**
     * 获取直属上级会员。
     */
    public function getParent(): ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    /**
     * 获取直属下级会员集合。
     */
    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(self::class, ['parent_id' => 'id']);
    }

    /**
     * 使用安全哈希保存备用登录密码。
     *
     * @param string $password 明文密码
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * 校验备用登录密码。
     *
     * @param string $password 待校验的明文密码
     */
    public function validatePassword(string $password): bool
    {
        return $this->password_hash !== null
            && Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * 按会员ID查找会员。
     *
     * @param int $id 会员ID
     */
    public static function findById(int $id): ?self
    {
        return self::findOne(['id' => $id]);
    }

    /**
     * 按推广码查找直属推广人。
     *
     * @param string $inviteCode 已规范化的推广码
     */
    public static function findByInviteCode(string $inviteCode): ?self
    {
        return self::findOne(['invite_code' => $inviteCode]);
    }

    /**
     * 判断用户名是否已被占用。
     *
     * @param string $username 规范化后的用户名
     * @param int|null $ignoreId 修改场景下忽略的会员ID
     */
    public static function existsByUsername(string $username, ?int $ignoreId = null): bool
    {
        return self::existsByAttribute('username', $username, $ignoreId);
    }

    /**
     * 判断手机号码是否已被占用。
     *
     * @param string $mobile 规范化后的手机号码
     * @param int|null $ignoreId 修改场景下忽略的会员ID
     */
    public static function existsByMobile(string $mobile, ?int $ignoreId = null): bool
    {
        return self::existsByAttribute('mobile', $mobile, $ignoreId);
    }

    /**
     * 判断邮箱是否已被占用。
     *
     * @param string $email 规范化后的邮箱
     * @param int|null $ignoreId 修改场景下忽略的会员ID
     */
    public static function existsByEmail(string $email, ?int $ignoreId = null): bool
    {
        return self::existsByAttribute('email', $email, $ignoreId);
    }

    /**
     * 返回会员状态字典。
     */
    public static function getStatusNames(): array
    {
        return [
            self::STATUS_ACTIVE => '正常',
            self::STATUS_FROZEN => '冻结',
        ];
    }

    /**
     * 返回当前会员的状态名称。
     */
    public function getStatusName(): string
    {
        return self::getStatusNames()[$this->status] ?? '未知';
    }

    /** 判断会员当前是否为正常状态。 */
    public function isActive(): bool
    {
        return (int) $this->status === self::STATUS_ACTIVE;
    }

    /** 判断会员当前是否为冻结状态。 */
    public function isFrozen(): bool
    {
        return (int) $this->status === self::STATUS_FROZEN;
    }

    /**
     * 返回会员注册方式字典。
     */
    public static function getRegisterTypeNames(): array
    {
        return [
            self::REGISTER_USERNAME => '用户名',
            self::REGISTER_QQ => 'QQ',
            self::REGISTER_WECHAT => '微信',
            self::REGISTER_ALIPAY => '支付宝',
            self::REGISTER_FACEBOOK => 'Facebook',
            self::REGISTER_X => 'X',
            self::REGISTER_TELEGRAM => 'Telegram',
            self::REGISTER_DOUYIN => '抖音',
            self::REGISTER_KUAISHOU => '快手',
            self::REGISTER_WEIBO => '微博',
            self::REGISTER_ADMIN => '后台创建',
        ];
    }

    /**
     * 返回当前会员的注册方式名称。
     */
    public function getRegisterTypeName(): string
    {
        return self::getRegisterTypeNames()[$this->register_type] ?? '未知';
    }

    /**
     * 判断指定字段值是否已存在。
     *
     * @param string $attribute 数据库字段名
     * @param string $value 待查询字段值
     * @param int|null $ignoreId 需要忽略的会员ID
     */
    private static function existsByAttribute(string $attribute, string $value, ?int $ignoreId): bool
    {
        $query = self::find()->where([$attribute => $value]);
        if ($ignoreId !== null) {
            $query->andWhere(['<>', 'id', $ignoreId]);
        }

        return $query->exists();
    }
}
