-- 开奖门户完整表结构，匹配截至 m261019_000002 的六条迁移。
-- 包含7张业务表及全部字段注释，适用于尚未安装门户的新环境。
-- 随后导入 portal.data.mysql.sql；详见 portal.README.md。
SET NAMES utf8mb4;

CREATE TABLE `portal_game` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '彩种ID',
  `code` varchar(32) NOT NULL COMMENT '彩种唯一代码，对应固定采集源',
  `name` varchar(64) NOT NULL COMMENT '彩种显示名称',
  `description` varchar(255) NOT NULL DEFAULT '' COMMENT '彩种与号码规则说明',
  `ball_count` smallint NOT NULL DEFAULT '3' COMMENT '每期开奖结果的号码个数',
  `ball_min` smallint NOT NULL DEFAULT '0' COMMENT '单个号码允许的最小值',
  `ball_max` smallint NOT NULL DEFAULT '9' COMMENT '单个号码允许的最大值',
  `sort_order` int NOT NULL DEFAULT '0' COMMENT '前台排序值，数值越小越靠前',
  `enabled` smallint NOT NULL DEFAULT '0' COMMENT '发布状态：0未发布，1已发布',
  `version` int NOT NULL DEFAULT '0' COMMENT '乐观锁版本号，防止并发修改覆盖',
  `create_time` datetime NOT NULL COMMENT '创建时间，北京时间',
  `update_time` datetime NOT NULL COMMENT '最后修改时间，北京时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_portal_game_code` (`code`),
  KEY `ix_portal_game_visible` (`enabled`,`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开奖门户彩种及号码规则';

CREATE TABLE `portal_draw` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '开奖记录ID',
  `game_id` int NOT NULL COMMENT '所属彩种ID，关联portal_game.id',
  `issue` varchar(64) NOT NULL COMMENT '开奖期号，与彩种ID共同唯一',
  `draw_time` datetime NOT NULL COMMENT '上游开奖时间，北京时间',
  `numbers` varchar(255) NOT NULL COMMENT '按开奖顺序保存的整数号码，以英文逗号分隔',
  `source` varchar(128) NOT NULL COMMENT '数据来源说明，标识采集接口或核实录入',
  `create_time` datetime NOT NULL COMMENT '记录入库时间，北京时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_portal_draw_issue` (`game_id`,`issue`),
  KEY `ix_portal_draw_time` (`game_id`,`draw_time`,`id`),
  CONSTRAINT `fk_portal_draw_game` FOREIGN KEY (`game_id`) REFERENCES `portal_game` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开奖门户真实开奖记录，同彩种同期号唯一';

CREATE TABLE `portal_notice` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '公告ID',
  `title` varchar(128) NOT NULL COMMENT '公告标题',
  `content` text NOT NULL COMMENT '公告正文，按纯文本展示',
  `enabled` smallint NOT NULL DEFAULT '0' COMMENT '发布状态：0未发布，1已发布',
  `version` int NOT NULL DEFAULT '0' COMMENT '乐观锁版本号，防止并发修改覆盖',
  `create_time` datetime NOT NULL COMMENT '创建时间，北京时间',
  `update_time` datetime NOT NULL COMMENT '最后修改时间，北京时间',
  PRIMARY KEY (`id`),
  KEY `ix_portal_notice_visible` (`enabled`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开奖门户公告';

CREATE TABLE `portal_audit` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '审计记录ID',
  `entity_type` varchar(32) NOT NULL COMMENT '被操作的业务实体类型',
  `entity_id` int NOT NULL COMMENT '被操作的业务记录ID',
  `action` varchar(32) NOT NULL COMMENT '业务操作标识',
  `operator_id` int NOT NULL COMMENT '执行操作的可信后台或系统身份ID',
  `create_time` datetime NOT NULL COMMENT '操作发生时间，北京时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开奖门户业务写入审计记录';

CREATE TABLE `portal_settings` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '站点配置ID，固定为1',
  `website` varchar(128) NOT NULL COMMENT '前台顶部显示域名',
  `title` varchar(128) NOT NULL COMMENT '站点标题',
  `landing` varchar(2048) NOT NULL COMMENT '登录与注册入口链接，也是登录二维码内容',
  `ad_wx` varchar(2048) NOT NULL DEFAULT '' COMMENT '合作联系链接',
  `ad_ww` varchar(2048) NOT NULL DEFAULT '' COMMENT '备用合作联系链接',
  `banner` varchar(128) NOT NULL COMMENT '顶部横幅图片路径或上传标识',
  `icon` varchar(128) NOT NULL COMMENT '站点图标图片路径或上传标识',
  `ad_icon` varchar(128) NOT NULL DEFAULT '' COMMENT '合作联系图标，空值时使用站点图标',
  `show_qrcode` smallint NOT NULL DEFAULT '1' COMMENT '是否显示登录二维码：0隐藏，1显示',
  `notice_scroll` text NOT NULL COMMENT '前台滚动公告文字',
  `ads_json` text NOT NULL COMMENT '有序广告JSON数组，含cover图片、link跳转和enabled启停',
  `version` int NOT NULL DEFAULT '0' COMMENT '乐观锁版本号，防止并发修改覆盖',
  `create_time` datetime NOT NULL COMMENT '创建时间，北京时间',
  `update_time` datetime NOT NULL COMMENT '最后修改时间，北京时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开奖门户站点、登录链接及广告配置单例';

CREATE TABLE `portal_prediction` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT '预测记录ID',
  `game_id` int NOT NULL COMMENT '所属彩种ID，关联portal_game.id',
  `issue` varchar(64) NOT NULL COMMENT '预测目标期号，与彩种ID共同唯一',
  `source_issue` varchar(64) NOT NULL COMMENT '生成预测时最新已开奖期号',
  `algorithm` varchar(64) NOT NULL COMMENT '本地预测算法名称及版本',
  `pdx` varchar(2) NOT NULL COMMENT '预测和值大小：大或小',
  `pds` varchar(2) NOT NULL COMMENT '预测和值单双：单或双',
  `sample_count` int NOT NULL COMMENT '生成预测实际使用的历史样本期数',
  `basis_json` text NOT NULL COMMENT '预测依据的历史开奖样本JSON快照',
  `basis_hash` varchar(64) NOT NULL COMMENT '历史样本快照的SHA-256摘要',
  `scores_json` text NOT NULL COMMENT '算法分类得分JSON快照',
  `target_time` datetime NOT NULL COMMENT '预测目标预计开奖时间，北京时间',
  `create_time` datetime NOT NULL COMMENT '预测生成时间，北京时间，必须早于目标开奖',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_portal_prediction_issue` (`game_id`,`issue`),
  CONSTRAINT `fk_portal_prediction_game` FOREIGN KEY (`game_id`) REFERENCES `portal_game` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开奖门户事前生成的本地预测及算法依据快照';

CREATE TABLE `portal_collection_state` (
  `game_id` int NOT NULL COMMENT '彩种ID，同时为主键，关联portal_game.id',
  `attempt_time` datetime NOT NULL COMMENT '最近一次完成采集尝试的时间，北京时间',
  `success_time` datetime DEFAULT NULL COMMENT '最近成功采集时间，失败时保留，未成功时为空',
  `error` varchar(255) NOT NULL DEFAULT '' COMMENT '最近一次采集错误说明，空字符串表示成功',
  `received` int NOT NULL DEFAULT '0' COMMENT '最近一次采集接收到的有效记录数',
  `inserted` int NOT NULL DEFAULT '0' COMMENT '最近一次采集新增入库数，已存在的同期记录不计入',
  PRIMARY KEY (`game_id`),
  CONSTRAINT `fk_portal_collection_game` FOREIGN KEY (`game_id`) REFERENCES `portal_game` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='开奖门户各彩种最近采集状态';
