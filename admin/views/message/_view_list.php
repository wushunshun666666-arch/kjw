<?php

use app\helpers\Html;
use common\models\notice\NoticeRecipient;

/* @var $data NoticeRecipient */

$message = $data->message;
?>
<tr data-id="<?= Html::encode($message->id) ?>">
    <td>
        <button type="button"
                class="btn btn-link detail p-0 text-decoration-none text-start"
                title="查看并标记为已读">
            <?php if (!$data->isRead()): ?>
                <span class="un-read text-danger me-1" aria-label="未读">●</span>
            <?php endif; ?>
            <?= Html::encode($message->title) ?>
        </button>
    </td>
    <td><?= Html::encode($message->content) ?></td>
    <td><?= Html::encode($message->getTypeName()) ?></td>
    <td><?= Html::encode($message->create_time) ?></td>
    <td>
        <?php if (!$data->isRead()): ?>
            <button type="button" class="btn btn-sm btn-outline-primary mark-read">标记为已读</button>
        <?php else: ?>
            <span class="text-success">已读</span>
        <?php endif; ?>
    </td>
</tr>
