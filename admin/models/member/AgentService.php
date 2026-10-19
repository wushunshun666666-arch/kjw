<?php

/**
 * 后台代理推广关系应用服务。
 *
 * 负责为代理管理页面准备推广关系概览和按需展开的卡片节点，不承担会员关系写入。
 */

namespace app\models\member;

use app\models\Service;
use common\models\member\Member;

/**
 * 代理推广关系后台应用服务。
 */
final class AgentService extends Service
{
    /**
     * 获取代理推广关系的统计概览。
     *
     * @return array{memberCount: int, rootMemberCount: int, agentMemberCount: int} 会员、根会员和拥有直属下级的会员数量
     */
    public function getPromotionOverview(): array
    {
        $statistics = Member::find()
            ->select([
                'memberCount' => 'COUNT(*)',
                'rootMemberCount' => 'SUM(parent_id IS NULL)',
                'agentMemberCount' => 'COUNT(DISTINCT parent_id)',
            ])
            ->asArray()
            ->one();

        return [
            'memberCount' => (int) ($statistics['memberCount'] ?? 0),
            'rootMemberCount' => (int) ($statistics['rootMemberCount'] ?? 0),
            'agentMemberCount' => (int) ($statistics['agentMemberCount'] ?? 0),
        ];
    }

    /**
     * 获取指定推广人的直属下级节点。
     *
     * 根层只展示拥有下级的推广人，避免把没有任何推广关系的独立会员列为代理。
     * 展开推广人后必须展示他的全部直属下级，包括自己没有下级的叶子会员。
     *
     * @param int|null $parentId 上级会员ID；null表示根会员
     * @return array<int, array<string, mixed>> 卡片树直属节点数组
     */
    public function getChildren(?int $parentId): array
    {
        $query = Member::find()->with('account')->orderBy(['member.id' => SORT_ASC]);
        if ($parentId === null) {
            $query
                ->andWhere(['member.parent_id' => null])
                ->andWhere([
                    'member.id' => Member::find()
                        ->select('parent_id')
                        ->where(['not', ['parent_id' => null]])
                        ->distinct(),
                ]);
        } else {
            $query->andWhere(['member.parent_id' => $parentId]);
        }

        $members = $query->all();
        if ($members === []) {
            return [];
        }

        $memberIds = array_map(
            static fn (Member $member): int => (int) $member->id,
            $members
        );
        $childCounts = Member::find()
            ->select([
                'parent_id',
                'childCount' => 'COUNT(*)',
            ])
            ->where(['in', 'parent_id', $memberIds])
            ->groupBy(['parent_id'])
            ->asArray()
            ->all();

        $childCountByParentId = [];
        foreach ($childCounts as $childCount) {
            $childCountByParentId[(int) $childCount['parent_id']] = (int) $childCount['childCount'];
        }

        $teamCountByMemberId = $this->getTeamCounts($memberIds);

        $nodes = [];
        foreach ($members as $member) {
            $memberId = (int) $member->id;
            $directChildCount = $childCountByParentId[$memberId] ?? 0;
            $nodes[] = [
                'id' => $memberId,
                'parentId' => $member->parent_id === null ? null : (int) $member->parent_id,
                'displayName' => $this->getDisplayName($member),
                'username' => (string) ($member->username ?? ''),
                'avatar' => (string) $member->avatar,
                'inviteCode' => (string) $member->invite_code,
                'status' => (int) $member->status,
                'statusName' => $member->getStatusName(),
                'isFrozen' => (int) $member->status === Member::STATUS_FROZEN,
                'directChildCount' => $directChildCount,
                'teamMemberCount' => $teamCountByMemberId[$memberId] ?? 0,
                'availableAmount' => $member->account === null
                    ? '0.0000'
                    : (string) $member->account->available_amount,
                'hasChildren' => $directChildCount > 0,
            ];
        }

        return $nodes;
    }

    /**
     * 返回卡片使用的会员显示名称。
     */
    private function getDisplayName(Member $member): string
    {
        $displayName = trim((string) ($member->nickname ?: $member->username));
        return $displayName === '' ? '未设置昵称' : $displayName;
    }

    /**
     * 分层查询当前节点的全部后代并统计团队人数。
     *
     * @param int[] $rootMemberIds 当前接口返回的节点ID
     * @return array<int, int> 以当前节点ID为键的后代会员数量
     */
    private function getTeamCounts(array $rootMemberIds): array
    {
        $teamCounts = array_fill_keys($rootMemberIds, 0);
        $ownerByMemberId = array_combine($rootMemberIds, $rootMemberIds);
        $frontierIds = $rootMemberIds;
        while ($frontierIds !== []) {
            $children = Member::find()
                ->select(['id', 'parent_id'])
                ->where(['parent_id' => $frontierIds])
                ->asArray()
                ->all();
            $nextFrontierIds = [];
            foreach ($children as $child) {
                $childId = (int) $child['id'];
                $parentId = (int) $child['parent_id'];
                if (isset($ownerByMemberId[$childId]) || !isset($ownerByMemberId[$parentId])) {
                    continue;
                }
                $rootMemberId = $ownerByMemberId[$parentId];
                $ownerByMemberId[$childId] = $rootMemberId;
                $teamCounts[$rootMemberId]++;
                $nextFrontierIds[] = $childId;
            }
            $frontierIds = $nextFrontierIds;
        }
        return $teamCounts;
    }
}
