<?php

namespace common\models\lottery;

/** 事前生成且不可变的本地预测。
 * @property int $id
 * @property int $game_id
 * @property string $issue
 * @property string $source_issue
 * @property string $algorithm
 * @property string $pdx
 * @property string $pds
 * @property int $sample_count
 * @property string $basis_json
 * @property string $basis_hash
 * @property string $scores_json
 * @property string $target_time
 * @property string $create_time
 */
final class Prediction extends \common\models\ActiveRecord
{
    /** 独立存储预测，禁止写入开奖结果表。 */
    public static function tableName(): string { return 'portal_prediction'; }

    /** 验证算法快照、枚举和唯一目标期号。 */
    public function rules(): array
    {
        return [
            [['game_id', 'issue', 'source_issue', 'algorithm', 'pdx', 'pds', 'sample_count', 'basis_json', 'basis_hash', 'scores_json', 'target_time', 'create_time'], 'required'],
            [['game_id'], 'integer', 'min' => 1],
            [['issue', 'source_issue'], 'match', 'pattern' => '/^[0-9]{1,14}$/D'],
            [['algorithm'], 'string', 'max' => 64],
            [['pdx'], 'in', 'range' => ['大', '小']], [['pds'], 'in', 'range' => ['单', '双']],
            [['sample_count'], 'integer', 'min' => PredictionAlgorithm::MIN_SAMPLES, 'max' => PredictionAlgorithm::WINDOW],
            [['basis_json'], 'string', 'max' => 60000], [['scores_json'], 'string', 'max' => 1000],
            [['basis_hash'], 'match', 'pattern' => '/^[a-f0-9]{64}$/D'],
            [['create_time', 'target_time'], 'datetime', 'format' => 'php:Y-m-d H:i:s'],
            [['game_id', 'issue'], 'unique', 'targetAttribute' => ['game_id', 'issue']],
        ];
    }

    /** 不允许通过普通实体保存或删除回改已发布预测。 */
    public function beforeSave($insert): bool
    {
        if (!$insert) { throw new \yii\base\InvalidCallException('已生成预测不可修改。'); }
        return parent::beforeSave($insert);
    }

    /** 保留完整的历史预测。 */
    public function beforeDelete(): bool { throw new \yii\base\InvalidCallException('已生成预测不可删除。'); }

    /** 适配原界面四种预测展示，组合和杀组由同一份快照推导。 */
    public function publicValues(): array
    {
        $dx = $this->pdx === '大' ? '小' : '大';
        $ds = $this->pds === '单' ? '双' : '单';
        return ['pdx' => $this->pdx, 'pds' => $this->pds, 'pbdx' => $dx, 'pbds' => $ds, 'pbdxds' => $dx . $ds];
    }
}
