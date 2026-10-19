<?php

namespace common\models\lottery;

/** 彩种名称数据实体。
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property int $ball_count
 * @property int $ball_min
 * @property int $ball_max
 * @property int $sort_order
 * @property int $enabled
 * @property int $version
 * @property string $create_time
 * @property string $update_time
 */
final class Game extends \common\models\ActiveRecord
{
    public const HIDDEN = 0;
    public const VISIBLE = 1;

    /** 返回领域表名。 */
    public static function tableName(): string { return 'portal_game'; }

    /** 验证持久化字段与号码边界。 */
    public function rules(): array
    {
        return [
            [['code', 'name', 'ball_count', 'ball_min', 'ball_max'], 'required'],
            [['code'], 'match', 'pattern' => '/^[a-z][a-z0-9_-]{1,31}$/'],
            [['code'], 'unique'],
            [['name'], 'string', 'max' => 64],
            [['description'], 'string', 'max' => 255],
            [['ball_count'], 'integer', 'min' => 1, 'max' => 20],
            [['ball_min', 'ball_max'], 'integer', 'min' => 0, 'max' => 999],
            [['ball_max'], 'compare', 'compareAttribute' => 'ball_min', 'operator' => '>=', 'type' => 'number'],
            [['sort_order'], 'integer', 'min' => 0, 'max' => 99999],
            [['enabled'], 'in', 'range' => [self::HIDDEN, self::VISIBLE]],
            [['version'], 'integer', 'min' => 0],
            [['create_time', 'update_time'], 'safe'],
        ];
    }

    /** 提供表单与展示字段名称。 */
    public function attributeLabels(): array { return ['code' => '彩种代码', 'name' => '彩种名称', 'description' => '说明', 'ball_count' => '号码个数', 'ball_min' => '最小号码', 'ball_max' => '最大号码', 'sort_order' => '排序', 'enabled' => '是否显示']; }

    /** 自动记录创建和修改时间。 */
    public function behaviors(): array { return [$this->timestampBehavior()]; }

    /** 使用版本列防止覆盖其他管理员的修改。 */
    public function optimisticLock(): string { return 'version'; }

    /** 返回发布状态字典。 */
    public static function getEnabledNames(): array { return [self::HIDDEN => '未发布', self::VISIBLE => '已发布']; }

    /** 返回当前发布状态。 */
    public function getEnabledName(): string { return self::getEnabledNames()[$this->enabled] ?? '未知'; }

    /** 判断前台是否可以展示。 */
    public function isVisible(): bool { return (int) $this->enabled === self::VISIBLE; }

    /** 返回按后台配置确定的号码范围。 */
    public function getNumberRange(): array
    {
        return range((int) $this->ball_min, (int) $this->ball_max);
    }

}
