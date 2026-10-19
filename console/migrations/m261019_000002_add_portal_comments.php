<?php

use yii\db\Migration;

/** 为已安装的开奖门户补全表和字段中文说明，保持原字段定义及数据不变。 */
final class m261019_000002_add_portal_comments extends Migration
{
    private const COMMENTS = [
        'portal_game' => ['开奖门户彩种及号码规则', [
            'id' => '彩种ID',
            'code' => '彩种唯一代码，对应固定采集源',
            'name' => '彩种显示名称',
            'description' => '彩种与号码规则说明',
            'ball_count' => '每期开奖结果的号码个数',
            'ball_min' => '单个号码允许的最小值',
            'ball_max' => '单个号码允许的最大值',
            'sort_order' => '前台排序值，数值越小越靠前',
            'enabled' => '发布状态：0未发布，1已发布',
            'version' => '乐观锁版本号，防止并发修改覆盖',
            'create_time' => '创建时间，北京时间',
            'update_time' => '最后修改时间，北京时间',
        ]],
        'portal_draw' => ['开奖门户真实开奖记录，同彩种同期号唯一', [
            'id' => '开奖记录ID',
            'game_id' => '所属彩种ID，关联portal_game.id',
            'issue' => '开奖期号，与彩种ID共同唯一',
            'draw_time' => '上游开奖时间，北京时间',
            'numbers' => '按开奖顺序保存的整数号码，以英文逗号分隔',
            'source' => '数据来源说明，标识采集接口或核实录入',
            'create_time' => '记录入库时间，北京时间',
        ]],
        'portal_notice' => ['开奖门户公告', [
            'id' => '公告ID',
            'title' => '公告标题',
            'content' => '公告正文，按纯文本展示',
            'enabled' => '发布状态：0未发布，1已发布',
            'version' => '乐观锁版本号，防止并发修改覆盖',
            'create_time' => '创建时间，北京时间',
            'update_time' => '最后修改时间，北京时间',
        ]],
        'portal_audit' => ['开奖门户业务写入审计记录', [
            'id' => '审计记录ID',
            'entity_type' => '被操作的业务实体类型',
            'entity_id' => '被操作的业务记录ID',
            'action' => '业务操作标识',
            'operator_id' => '执行操作的可信后台或系统身份ID',
            'create_time' => '操作发生时间，北京时间',
        ]],
        'portal_settings' => ['开奖门户站点、登录链接及广告配置单例', [
            'id' => '站点配置ID，固定为1',
            'website' => '前台顶部显示域名',
            'title' => '站点标题',
            'landing' => '登录与注册入口链接，也是登录二维码内容',
            'ad_wx' => '合作联系链接',
            'ad_ww' => '备用合作联系链接',
            'banner' => '顶部横幅图片路径或上传标识',
            'icon' => '站点图标图片路径或上传标识',
            'ad_icon' => '合作联系图标，空值时使用站点图标',
            'show_qrcode' => '是否显示登录二维码：0隐藏，1显示',
            'notice_scroll' => '前台滚动公告文字',
            'ads_json' => '有序广告JSON数组，含cover图片、link跳转和enabled启停',
            'version' => '乐观锁版本号，防止并发修改覆盖',
            'create_time' => '创建时间，北京时间',
            'update_time' => '最后修改时间，北京时间',
        ]],
        'portal_prediction' => ['开奖门户事前生成的本地预测及算法依据快照', [
            'id' => '预测记录ID',
            'game_id' => '所属彩种ID，关联portal_game.id',
            'issue' => '预测目标期号，与彩种ID共同唯一',
            'source_issue' => '生成预测时最新已开奖期号',
            'algorithm' => '本地预测算法名称及版本',
            'pdx' => '预测和值大小：大或小',
            'pds' => '预测和值单双：单或双',
            'sample_count' => '生成预测实际使用的历史样本期数',
            'basis_json' => '预测依据的历史开奖样本JSON快照',
            'basis_hash' => '历史样本快照的SHA-256摘要',
            'scores_json' => '算法分类得分JSON快照',
            'target_time' => '预测目标预计开奖时间，北京时间',
            'create_time' => '预测生成时间，北京时间，必须早于目标开奖',
        ]],
        'portal_collection_state' => ['开奖门户各彩种最近采集状态', [
            'game_id' => '彩种ID，同时为主键，关联portal_game.id',
            'attempt_time' => '最近一次完成采集尝试的时间，北京时间',
            'success_time' => '最近成功采集时间，失败时保留，未成功时为空',
            'error' => '最近一次采集错误说明，空字符串表示成功',
            'received' => '最近一次采集接收到的有效记录数',
            'inserted' => '最近一次采集新增入库数，已存在的同期记录不计入',
        ]],
    ];

    /** 使用 Yii 注释接口保留字段类型、默认值、自增属性及索引外键。 */
    public function safeUp(): void
    {
        foreach (self::COMMENTS as $table => [$description, $columns]) {
            $this->addCommentOnTable($table, $description);
            foreach ($columns as $column => $comment) {
                $this->addCommentOnColumn($table, $column, $comment);
            }
        }
    }

    /** 字段说明为长期维护资料，不自动回滚删除。 */
    public function safeDown(): bool
    {
        return false;
    }
}
