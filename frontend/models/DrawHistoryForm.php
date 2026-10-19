<?php

namespace frontend\models;

use common\models\lottery\FeedCatalog;

/** 公开开奖历史分页输入，只接受固定彩种、受限页码和数据库快照。 */
final class DrawHistoryForm extends \common\models\FormModel
{
    public $game = 'jnd28';
    public $page = 1;
    public $page_size = 100;
    public $snapshot = 0;

    /** 与公开 GET 接口保持平级参数。 */
    public function formName(): string
    {
        return '';
    }

    /** 限制查询范围，拒绝数组、浮点数和未知彩种。 */
    public function rules(): array
    {
        return [
            [['game', 'page', 'page_size', 'snapshot'], 'required'],
            [['game'], 'in', 'range' => array_keys(FeedCatalog::GAMES)],
            [['page'], 'integer', 'min' => 1, 'max' => 10000],
            [['page_size'], 'integer'],
            [['page_size'], 'in', 'range' => [20, 50, 100]],
            [['snapshot'], 'integer', 'min' => 0, 'max' => 9007199254740991],
        ];
    }
}
