<?php

namespace common\models\lottery;

/** 同一期历史冲突，区别于需要停止入库的其他业务错误。 */
final class DrawConflict extends \common\models\Error
{
    public function __construct(Game $game, Draw $stored, DrawInput $input, string $numbers)
    {
        $differences = [];
        if ($stored->numbers !== $numbers) {
            $differences[] = "号码不同（库内 {$stored->numbers}；接口 {$numbers}）";
        }
        if ($stored->draw_time !== $input->draw_time) {
            $differences[] = "开奖时间不同（库内 {$stored->draw_time}；接口 {$input->draw_time}）";
        }
        parent::__construct($game->name . '[' . $game->code . '] 第' . $input->issue . '期：'
            . implode('；', $differences) . '；历史开奖未覆盖。');
    }
}
