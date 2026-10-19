<?php

namespace frontend\models;

/** 中心只读共享用例，复用本站数据库格式且不导出站点外观或私有算法依据。 */
final class SharedDataService
{
    /** 返回已校验彩种的最新开奖；共享发布独立于中心网站的彩种显隐。 */
    public function latest(GameForm $form): array
    {
        $data = $form->game === 'jnd28'
            ? (new ReferencePortalService())->open(true)
            : (new GamePortalService())->open($form->game, true);
        $data['game']['code'] = $form->game;
        return $data;
    }

    /** 在固定快照内共享历史记录，保持各彩种公开列表协议。 */
    public function history(DrawHistoryForm $form): array
    {
        return (new DrawHistoryService())->search($form, true);
    }
}
