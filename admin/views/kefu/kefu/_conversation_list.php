<?php

/**
 * 客服工作台左侧会话列表单行模板。
 *
 * @var common\models\kefu\KefuConversation $data 当前长期会话
 */

use app\helpers\Html;

// 列表只展示必要资料，完整会员信息在右侧详情区域加载。
$member = $data->member;
$userName = $data->user?->name ?: '无人接待';
?>
<tr class="kefu-conversation-item" data-id="<?= (int) $data->id ?>"
    data-member-id="<?= (int) $member->id ?>">
    <td>
        <div class="d-flex gap-2 align-items-start">
            <span class="kefu-avatar kefu-member-avatar"><?= Html::encode(mb_substr(
                $member->nickname ?: $member->username ?: '客',
                0,
                1
            )) ?></span>
            <div class="flex-grow-1 min-w-0">
                <div class="d-flex justify-content-between gap-2 align-items-center">
                    <strong class="text-truncate"><?= Html::encode(
                        $member->nickname ?: $member->username ?: '会员' . $member->id
                    ) ?></strong>
                    <?php if ((int) $data->user_unread_count > 0) : ?>
                        <span class="badge rounded-pill text-bg-danger kefu-conversation-unread-count"><?= (int) $data->user_unread_count ?></span>
                    <?php endif; ?>
                </div>
                <div class="small text-secondary text-truncate">
                    <?= Html::encode($data->last_message_preview ?: '暂无消息') ?>
                </div>
                <div class="kefu-conversation-meta">
                    <span class="kefu-member-online-dot" title="离线"></span>
                    <span class="kefu-account-amount">币 <?= Html::encode(
                        $member->account?->available_amount ?: '0.0000'
                    ) ?></span>
                    <span><?= Html::encode($userName) ?></span>
                    <time><?= Html::encode(Yii::$app->formatter->asDatetime(
                        $data->last_message_time ?: $data->create_time,
                        'php:m-d H:i'
                    )) ?></time>
                </div>
            </div>
        </div>
    </td>
</tr>
