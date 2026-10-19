<?php

namespace common\models\lottery;

/** 各彩种最近采集结果，仅供状态查询。
 * @property int $game_id
 * @property string $attempt_time
 * @property string|null $success_time
 * @property string $error
 * @property int $received
 * @property int $inserted
 */
final class CollectionState extends \common\models\ActiveRecord
{
    /** 返回采集状态表。 */
    public static function tableName(): string { return 'portal_collection_state'; }

    /** 校验状态快照字段。 */
    public function rules(): array
    {
        return [
            [['game_id', 'attempt_time'], 'required'],
            [['game_id'], 'integer', 'min' => 1],
            [['received', 'inserted'], 'integer', 'min' => 0],
            [['attempt_time', 'success_time'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['error'], 'string', 'max' => 255],
        ];
    }
}
