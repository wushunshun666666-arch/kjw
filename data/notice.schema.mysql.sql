# 后台站内通知初始化脚本；先创建主表，再创建带外键的接收表。
SET NAMES utf8mb4;

CREATE TABLE `notice` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '站内消息ID',
  `type` SMALLINT NOT NULL COMMENT '消息类型（1000普通通知，1100上下分通知，1200系统告警，1300库存不足）',
  `title` VARCHAR(100) NOT NULL COMMENT '消息标题',
  `content` VARCHAR(1000) NOT NULL COMMENT '消息内容',
  `extras` TEXT NULL COMMENT '业务扩展参数JSON',
  `create_user_id` INT NULL COMMENT '创建消息的后台用户ID，系统消息为空',
  `create_time` DATETIME NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_notice_type_id` (`type`, `id`),
  KEY `idx_notice_create_time_id` (`create_time`, `id`),
  CONSTRAINT `fk_notice_create_user` FOREIGN KEY (`create_user_id`) REFERENCES `user` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台站内消息';

CREATE TABLE `notice_recipient` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '消息接收记录ID',
  `message_id` BIGINT UNSIGNED NOT NULL COMMENT '站内消息ID',
  `user_id` INT NOT NULL COMMENT '后台接收用户ID',
  `read_time` DATETIME NULL COMMENT '首次阅读时间',
  `create_time` DATETIME NOT NULL COMMENT '投递时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_notice_recipient_message_user` (`message_id`, `user_id`),
  KEY `idx_notice_recipient_user_read_message` (`user_id`, `read_time`, `message_id`),
  CONSTRAINT `fk_notice_recipient_message` FOREIGN KEY (`message_id`) REFERENCES `notice` (`id`) ON UPDATE RESTRICT ON DELETE CASCADE,
  CONSTRAINT `fk_notice_recipient_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='后台站内消息接收记录';
