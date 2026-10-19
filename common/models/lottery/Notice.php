<?php

namespace common\models\lottery;

/** 公告标题数据实体。
 * @property int $id
 * @property string $title
 * @property string $content
 * @property int $enabled
 * @property int $version
 * @property string $create_time
 * @property string $update_time
 */
final class Notice extends \common\models\ActiveRecord
{
    public const HIDDEN = 0;
    public const VISIBLE = 1;

    /** 返回领域表名。 */
    public static function tableName(): string { return 'portal_notice'; }

    /** 验证持久化字段与号码边界。 */
    public function rules(): array
    {
        return [
            [['title', 'content'], 'required'],
            [['title'], 'string', 'max' => 128],
            [['content'], 'string', 'max' => 20000],
            [['enabled'], 'in', 'range' => [self::HIDDEN, self::VISIBLE]],
            [['version'], 'integer', 'min' => 0],
            [['create_time', 'update_time'], 'safe'],
        ];
    }

    /** 提供表单与展示字段名称。 */
    public function attributeLabels(): array { return ['title' => '公告标题', 'content' => '公告正文', 'enabled' => '是否发布']; }

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

}
