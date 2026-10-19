<?php

/**
 * 后台会员应用服务。
 *
 * 连接控制器、表单模型和会员领域逻辑，不直接承载数据库写入规则。
 */

namespace app\models\member;

use app\models\Service;
use common\models\Error;
use common\models\member\Member;
use common\models\member\MemberModel;
use yii\data\ActiveDataProvider;

/**
 * 会员后台管理服务。
 */
final class MemberService extends Service
{
    /**
     * 根据搜索表单构造会员列表数据源。
     *
     * @return ActiveDataProvider 会员列表数据提供器
     */
    public function search(SearchForm $form)
    {
        $query = Member::find();
        $query ->with(['account', 'parent']);
        $query->andFilterWhere(['member.id' => $form->id]);
        $query->andFilterWhere(['like', 'member.username', $form->username]);
        $query->andFilterWhere(['like', 'member.nickname', $form->nickname]);
        $query->andFilterWhere(['like', 'member.mobile', $form->mobile]);
        $query->andFilterWhere(['like', 'member.email', $form->email]);
        $query->andFilterWhere(['member.status' => $form->status]);
        $query->andFilterWhere(['member.register_type' => $form->registerType]);

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $this->getOption('pagesize')],
            'sort' => [
                'attributes' => [
                    'member.id',
                    'member.username',
                    'member.nickname' => [
                        'asc' => ['CONVERT(member.nickname using gbk)' => SORT_ASC],
                        'desc' => ['CONVERT(member.nickname using gbk)' => SORT_DESC],
                    ],
                    'member.status',
                    'member.register_type',
                    'member.create_time' => [
                        'asc' => ['member.id' => SORT_ASC],
                        'desc' => ['member.id' => SORT_DESC],
                    ],
                ],
                'defaultOrder' => ['member.id' => SORT_DESC],
            ],
        ]);
    }

    /**
     * 获取会员详情及其账户、直属上级和社交身份。
     *
     * @param int $id 会员ID
     * @return Member|false 会员不存在时返回false并记录业务错误
     */
    public function getDetail(int $id): Member|false
    {
        $memberModel = $this->getMemberModel($id);
        if ($memberModel === false) {
            return false;
        }

        $member = $memberModel->getData();
        $member->populateRelation('account', $member->getAccount()->one());
        $member->populateRelation('parent', $member->getParent()->one());
        $member->populateRelation('authIdentities', $member->getAuthIdentities()
            ->orderBy(['id' => SORT_ASC])
            ->all());
        return $member;
    }

    /**
     * 通过后台新增表单创建会员和账户。
     *
     * @param AddForm $form 已验证的新增表单
     */
    public function add(AddForm $form): bool
    {
        $attributes = $form->getSafeAttributes();
        $attributes['register_type'] = Member::REGISTER_ADMIN;
        $result = MemberModel::create($attributes);
        if (Error::isError($result)) {
            return $this->addErrors($result->getErrors());
        }

        return true;
    }

    /**
     * 修改会员基础资料。
     *
     * @param int $id 会员ID
     * @param ModifyForm $form 已验证的修改表单
     */
    public function modify(int $id, ModifyForm $form): bool
    {
        $member = $this->getMemberModel($id);
        if ($member === false) {
            return false;
        }

        if (!$member->modify($form->getSafeAttributes())) {
            return $this->addErrors($member->getErrors());
        }

        return true;
    }

    /**
     * 冻结指定会员。
     *
     * @param int $id 会员ID
     */
    public function freeze(int $id): bool
    {
        $member = $this->getMemberModel($id);
        if ($member === false) {
            return false;
        }

        if (!$member->freeze()) {
            return $this->addErrors($member->getErrors());
        }

        return true;
    }

    /**
     * 解冻指定会员。
     *
     * @param int $id 会员ID
     */
    public function unfreeze(int $id): bool
    {
        $member = $this->getMemberModel($id);
        if ($member === false) {
            return false;
        }

        if (!$member->unfreeze()) {
            return $this->addErrors($member->getErrors());
        }

        return true;
    }

    /**
     * 返回会员状态字典。
     */
    public function getStatusNames(): array
    {
        return Member::getStatusNames();
    }

    /**
     * 返回会员注册方式字典。
     */
    public function getRegisterTypeNames(): array
    {
        return Member::getRegisterTypeNames();
    }

    /**
     * 获取会员领域对象并统一处理会员不存在错误。
     *
     * @param int $id 会员ID
     */
    private function getMemberModel(int $id): MemberModel|false
    {
        $member = MemberModel::getById($id);
        if ($member === null) {
            $this->setError('会员不存在。');
            return false;
        }

        return $member;
    }
}
