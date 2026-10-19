<?php

namespace common\models\lottery;

use yii\db\ActiveQuery;

/** 不可变的开奖记录实体。
 * @property int $id
 * @property int $game_id
 * @property string $issue
 * @property string $draw_time
 * @property string $numbers
 * @property string $source
 * @property string $create_time
 * @property-read Game $game
 */
final class Draw extends \common\models\ActiveRecord
{
    /** 返回开奖记录表名。 */
    public static function tableName(): string { return 'portal_draw'; }

    /** 验证开奖数据和期号唯一性。 */
    public function rules(): array
    {
        return [
            [['game_id', 'issue', 'draw_time', 'numbers', 'source', 'create_time'], 'required'],
            [['game_id'], 'integer', 'min' => 1],
            [['issue'], 'match', 'pattern' => '/^[a-zA-Z0-9_-]{1,64}$/'],
            [['numbers'], 'string', 'max' => 255],
            [['source'], 'string', 'max' => 128],
            [['draw_time'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['create_time'], 'safe'],
            [['game_id', 'issue'], 'unique', 'targetAttribute' => ['game_id', 'issue']],
            [['game_id'], 'exist', 'targetClass' => Game::class, 'targetAttribute' => 'id'],
        ];
    }

    /** 关联所属彩种。 */
    public function getGame(): ActiveQuery { return $this->hasOne(Game::class, ['id' => 'game_id']); }

    /** 返回标准整数号码序列。 */
    public function getBalls(): array { return array_map('intval', explode(',', $this->numbers)); }

    /** 返回原始号码和值，不推断玩法。 */
    public function getTotal(): int { return array_sum($this->getBalls()); }
}
