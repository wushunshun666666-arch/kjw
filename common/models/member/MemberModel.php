<?php

/**
 * 会员领域逻辑模型。
 *
 * 集中处理会员创建、资料修改、状态切换和推广归属等业务规则。
 */

namespace common\models\member;

use common\models\Error;
use common\models\LogicModel;
use mylibs\yii\db\Transaction;
use yii\db\Expression;

/**
 * 会员领域服务对象，所有会员写操作通过本对象完成。
 */
final class MemberModel extends LogicModel
{
    /** @var Member 当前处理的会员实体 */
    private Member $member;

    /**
     * 构造会员领域对象。
     *
     * @param Member $member 当前处理的会员实体
     */
    public function __construct(Member $member)
    {
        $this->member = $member;
        parent::__construct();
    }

    /**
     * 按会员ID创建领域对象。
     *
     * @param int $id 会员ID
     */
    public static function getById(int $id): ?self
    {
        $member = Member::findById($id);
        return $member === null ? null : new self($member);
    }

    /**
     * 使用会员备用用户名和密码完成真实登录。
     *
     * 登录成功会更新最后登录时间；冻结会员和未设置密码的社交会员不能使用此入口。
     *
     * @param array{username: string, password: string} $attributes 已验证的登录凭据
     * @return self|Error 登录成功的会员领域对象或认证错误
     */
    public static function loginWithPassword(array $attributes): self|Error
    {
        $username = self::normalizeUsername($attributes['username'] ?? null);
        $password = (string) ($attributes['password'] ?? '');
        if ($username === null || $password === '') {
            return new Error('用户名或密码错误。');
        }
        $member = Member::findOne(['username' => $username]);
        if ($member === null || !$member->validatePassword($password)) {
            return new Error('用户名或密码错误。');
        }
        if ((int) $member->status !== Member::STATUS_ACTIVE) {
            return new Error('会员已被冻结，不能登录。');
        }
        $member->last_login_time = new Expression('NOW()');
        if (!$member->update(true, ['last_login_time', 'update_time'])) {
            return new Error($member->getErrors());
        }
        return new self($member);
    }

    /**
     * 创建会员及其一对一账户。
     *
     * 用户名和密码允许同时留空，以支持社交账号首次注册；推广关系通过上级推广码建立。
     *
     * @param array $attributes 已通过表单初步校验的会员资料
     * @return self|Error 成功返回会员领域对象，业务失败返回错误对象
     */
    public static function create(array $attributes): self|Error
    {
        $username = self::normalizeUsername($attributes['username'] ?? null);
        $password = (string) ($attributes['password'] ?? '');
        $mobile = self::normalizeMobile($attributes['mobile'] ?? null);
        $email = self::normalizeEmail($attributes['email'] ?? null);
        $parentInviteCode = self::normalizeInviteCode($attributes['parentInviteCode'] ?? null);
        $registerType = (int) ($attributes['register_type'] ?? Member::REGISTER_USERNAME);

        if (($username === null) !== ($password === '')) {
            return new Error('用户名和密码必须同时设置或同时留空。', 'username');
        }
        if (!isset(Member::getRegisterTypeNames()[$registerType])) {
            return new Error('注册方式无效。', 'register_type');
        }

        $parent = $parentInviteCode === null ? null : Member::findByInviteCode($parentInviteCode);
        if ($parentInviteCode !== null && $parent === null) {
            return new Error('上级推广码不存在。', 'parentInviteCode');
        }

        $uniqueError = self::checkUnique($username, $mobile, $email);
        if ($uniqueError !== null) {
            return $uniqueError;
        }

        $transaction = new Transaction();
        $member = new Member();
        $member->parent_id = $parent?->id;
        $member->invite_code = self::generateInviteCode();
        $member->username = $username;
        if ($password !== '') {
            $member->setPassword($password);
        }
        $member->nickname = (string) ($attributes['nickname'] ?? '');
        $member->avatar = (string) ($attributes['avatar'] ?? '');
        $member->mobile = $mobile;
        $member->email = $email;
        $member->status = Member::STATUS_ACTIVE;
        $member->register_type = $registerType;
        $member->auth_version = 1;

        if (!$member->save()) {
            $transaction->rollback();
            return new Error($member->getErrors());
        }

        $account = new Account();
        $account->member_id = $member->id;
        $account->available_amount = '0.0000';
        $account->frozen_amount = '0.0000';
        $account->row_version = 0;
        if (!$account->save()) {
            $transaction->rollback();
            return new Error($account->getErrors());
        }

        $transaction->commit();
        return new self($member);
    }

    /**
     * 创建会员、账户和社交身份绑定。
     *
     * 用于第三方一键登录：在单个事务中创建无用户名密码的会员、账户和社交身份绑定记录。
     *
     * @param array $attributes 会员基础属性（nickname、avatar、register_type）
     * @param array $identityAttributes 社交身份属性（provider、provider_app_id、provider_user_id、union_id、nickname、avatar）
     * @return self|Error 成功返回会员领域对象，业务失败返回错误对象
     */
    public static function createWithSocialIdentity(array $attributes, array $identityAttributes): self|Error
    {
        $registerType = (int) ($attributes['register_type'] ?? Member::REGISTER_USERNAME);
        if (!isset(Member::getRegisterTypeNames()[$registerType])) {
            return new Error('注册方式无效。', 'register_type');
        }

        $provider = (int) ($identityAttributes['provider'] ?? 0);
        $providerAppId = trim((string) ($identityAttributes['provider_app_id'] ?? ''));
        $providerUserId = trim((string) ($identityAttributes['provider_user_id'] ?? ''));
        if ($provider === 0 || $providerAppId === '' || $providerUserId === '') {
            return new Error('社交平台身份信息不完整。', 'provider');
        }

        $existingIdentity = MemberAuthIdentity::find()
            ->where([
                'provider' => $provider,
                'provider_app_id' => $providerAppId,
                'provider_user_id' => $providerUserId,
                'status' => MemberAuthIdentity::STATUS_BOUND,
            ])
            ->exists();
        if ($existingIdentity) {
            return new Error('该社交账号已经绑定其他会员。', 'provider_user_id');
        }

        $transaction = new Transaction();
        $member = new Member();
        $member->parent_id = null;
        $member->invite_code = self::generateInviteCode();
        $member->username = null;
        $member->nickname = (string) ($attributes['nickname'] ?? '');
        $member->avatar = (string) ($attributes['avatar'] ?? '');
        $member->status = Member::STATUS_ACTIVE;
        $member->register_type = $registerType;
        $member->auth_version = 1;

        if (!$member->save()) {
            $transaction->rollback();
            return new Error($member->getErrors());
        }

        $account = new Account();
        $account->member_id = $member->id;
        $account->available_amount = '0.0000';
        $account->frozen_amount = '0.0000';
        $account->row_version = 0;
        if (!$account->save()) {
            $transaction->rollback();
            return new Error($account->getErrors());
        }

        $identity = new MemberAuthIdentity();
        $identity->member_id = $member->id;
        $identity->provider = $provider;
        $identity->provider_app_id = $providerAppId;
        $identity->provider_user_id = $providerUserId;
        $identity->union_id = $identityAttributes['union_id'] ?? null;
        $identity->nickname = (string) ($identityAttributes['nickname'] ?? '');
        $identity->avatar = (string) ($identityAttributes['avatar'] ?? '');
        $identity->status = MemberAuthIdentity::STATUS_BOUND;
        if (!$identity->save()) {
            $transaction->rollback();
            return new Error($identity->getErrors());
        }

        $transaction->commit();
        return new self($member);
    }

    /**
     * 更新已有社交身份的快照和最近登录时间。
     *
     * @param string $providerUserId 平台稳定用户ID
     * @param array $attributes 社交身份属性（nickname、avatar）
     */
    public function touchSocialIdentity(
        int $provider,
        string $providerAppId,
        string $providerUserId,
        array $attributes
    ): bool
    {
        $identity = MemberAuthIdentity::find()
            ->where([
                'provider' => $provider,
                'provider_app_id' => $providerAppId,
                'provider_user_id' => $providerUserId,
                'status' => MemberAuthIdentity::STATUS_BOUND,
            ])
            ->one();
        if ($identity === null) {
            return $this->setError('社交身份绑定记录不存在。');
        }

        $identity->nickname = (string) ($attributes['nickname'] ?: $identity->nickname);
        $identity->avatar = (string) ($attributes['avatar'] ?: $identity->avatar);
        $identity->last_login_time = new Expression('NOW()');
        if (!$identity->update(true, ['nickname', 'avatar', 'last_login_time', 'update_time'])) {
            return $this->addErrors($identity->getErrors());
        }

        return true;
    }

    /**
     * 按平台和应用ID查找已绑定的社交身份。
     */
    public static function findSocialIdentity(int $provider, string $providerAppId, string $providerUserId): ?MemberAuthIdentity
    {
        return MemberAuthIdentity::find()
            ->where([
                'provider' => $provider,
                'provider_app_id' => $providerAppId,
                'provider_user_id' => $providerUserId,
                'status' => MemberAuthIdentity::STATUS_BOUND,
            ])
            ->one();
    }

    /**
     * 修改允许后台维护的会员基础资料。
     *
     * 推广归属、用户名和账户数据不在此方法中修改。
     *
     * @param array $attributes 已通过表单校验的会员资料
     */
    public function modify(array $attributes): bool
    {
        $mobile = self::normalizeMobile($attributes['mobile'] ?? null);
        $email = self::normalizeEmail($attributes['email'] ?? null);
        $uniqueError = self::checkUnique(null, $mobile, $email, (int) $this->member->id);
        if ($uniqueError !== null) {
            return $this->addErrors($uniqueError->getErrors());
        }

        $this->member->nickname = (string) $attributes['nickname'];
        $this->member->avatar = (string) ($attributes['avatar'] ?? '');
        $this->member->mobile = $mobile;
        $this->member->email = $email;

        if (!$this->member->update(true, ['nickname', 'avatar', 'mobile', 'email', 'update_time'])) {
            return $this->addErrors($this->member->getErrors());
        }

        return true;
    }

    /**
     * 冻结会员，并提升认证版本使旧登录态失效。
     */
    public function freeze(): bool
    {
        return $this->changeStatus(Member::STATUS_ACTIVE, Member::STATUS_FROZEN, '会员已经冻结。');
    }

    /**
     * 解冻会员，并再次提升认证版本。
     */
    public function unfreeze(): bool
    {
        return $this->changeStatus(Member::STATUS_FROZEN, Member::STATUS_ACTIVE, '会员当前不是冻结状态。');
    }

    /**
     * 返回当前会员实体。
     */
    public function getData(): Member
    {
        return $this->member;
    }

    /**
     * 规范化备用登录用户名。
     *
     * @param mixed $value 原始用户名
     */
    public static function normalizeUsername(mixed $value): ?string
    {
        $value = strtolower(trim((string) ($value ?? '')));
        return $value === '' ? null : $value;
    }

    /**
     * 规范化国际手机号码，移除空格和中划线。
     *
     * @param mixed $value 原始手机号码
     */
    public static function normalizeMobile(mixed $value): ?string
    {
        $value = preg_replace('/[\s-]+/', '', trim((string) ($value ?? '')));
        return $value === '' ? null : $value;
    }

    /**
     * 规范化邮箱并统一为小写。
     *
     * @param mixed $value 原始邮箱
     */
    public static function normalizeEmail(mixed $value): ?string
    {
        $value = strtolower(trim((string) ($value ?? '')));
        return $value === '' ? null : $value;
    }

    /**
     * 规范化推广码并统一为大写。
     *
     * @param mixed $value 原始推广码
     */
    public static function normalizeInviteCode(mixed $value): ?string
    {
        $value = strtoupper(trim((string) ($value ?? '')));
        return $value === '' ? null : $value;
    }

    /**
     * 检查用户名、手机号码和邮箱的唯一性。
     *
     * @param string|null $username 规范化后的用户名
     * @param string|null $mobile 规范化后的手机号码
     * @param string|null $email 规范化后的邮箱
     * @param int|null $ignoreId 修改时忽略的会员ID
     */
    private static function checkUnique(
        ?string $username,
        ?string $mobile,
        ?string $email,
        ?int $ignoreId = null
    ): ?Error {
        if ($username !== null && Member::existsByUsername($username, $ignoreId)) {
            return new Error('用户名已经存在。', 'username');
        }
        if ($mobile !== null && Member::existsByMobile($mobile, $ignoreId)) {
            return new Error('手机号码已经存在。', 'mobile');
        }
        if ($email !== null && Member::existsByEmail($email, $ignoreId)) {
            return new Error('Email已经存在。', 'email');
        }

        return null;
    }

    /**
     * 生成10位不易混淆的唯一推广码。
     */
    private static function generateInviteCode(): string
    {
        $alphabet = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        // 字符表最后一个下标，用于限制随机字符的取值范围。
        $lastIndex = strlen($alphabet) - 1;

        do {
            $inviteCode = '';
            for ($index = 0; $index < 10; $index++) {
                $inviteCode .= $alphabet[random_int(0, $lastIndex)];
            }
        } while (Member::find()->where(['invite_code' => $inviteCode])->exists());

        return $inviteCode;
    }

    /**
     * 以乐观条件原子切换会员状态。
     *
     * @param int $oldStatus 操作前必须匹配的状态
     * @param int $newStatus 操作后的目标状态
     * @param string $invalidMessage 状态不允许切换时的提示
     */
    private function changeStatus(int $oldStatus, int $newStatus, string $invalidMessage): bool
    {
        if ((int) $this->member->status !== $oldStatus) {
            return $this->setError($invalidMessage);
        }

        $affected = Member::updateAll(
            [
                'status' => $newStatus,
                'auth_version' => new Expression('auth_version + 1'),
                'update_time' => Member::expressionNow(),
            ],
            ['id' => $this->member->id, 'status' => $oldStatus]
        );
        if ($affected !== 1) {
            return $this->setError('会员状态已经变化，请刷新后重试。');
        }

        $this->member->status = $newStatus;
        $this->member->auth_version = (int) $this->member->auth_version + 1;
        return true;
    }
}
