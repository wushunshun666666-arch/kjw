# 客服模块数据库结构初始化脚本
SET NAMES utf8mb4;


# 客服长期会话表：每个会员只保留一条会话，记录当前接待人和列表摘要
CREATE TABLE `kefu_conversation` (
   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '客服会话ID',
   `member_id` BIGINT UNSIGNED NOT NULL COMMENT '会员ID',
   `user_id` INT NULL COMMENT '当前接待客服用户ID，无人接待时为空',
   `status` SMALLINT NOT NULL COMMENT '会话状态（1000等待接待，1100接待中，9000已结束）',
   `last_message_id` BIGINT UNSIGNED NULL COMMENT '最后一条消息ID',
   `last_message_type` SMALLINT NULL COMMENT '最后消息类型',
   `last_message_preview` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '最后消息摘要',
   `last_message_time` DATETIME NULL COMMENT '最近聊天时间',
   `member_unread_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '会员未读消息数',
   `user_unread_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '客服未读消息数',
   `create_time` DATETIME NOT NULL COMMENT '创建时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   UNIQUE KEY `uk_kefu_conversation_member` (`member_id`),
   KEY `idx_kefu_conversation_status_time` (`status`, `last_message_time`),
   KEY `idx_kefu_conversation_user_status_time` (`user_id`, `status`, `last_message_time`),
   CONSTRAINT `fk_kefu_conversation_member`
      FOREIGN KEY (`member_id`) REFERENCES `member` (`id`)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT `fk_kefu_conversation_user`
      FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT '客服长期会话';


# 客服消息表：永久保存文字、图片、语音、视频和接待操作记录
CREATE TABLE `kefu_message` (
   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '客服消息ID',
   `conversation_id` BIGINT UNSIGNED NOT NULL COMMENT '客服会话ID',
   `sender_type` SMALLINT NOT NULL COMMENT '发送者类型（1000会员，2000客服，9000系统）',
   `sender_member_id` BIGINT UNSIGNED NULL COMMENT '会员发送者ID',
   `sender_user_id` INT NULL COMMENT '客服发送者ID',
   `message_type` SMALLINT NOT NULL COMMENT '消息类型（1000文字，2000图片，3000语音，4000视频，9000系统）',
   `content` TEXT NULL COMMENT '文字、表情或系统消息内容',
   `media_url` VARCHAR(500) NULL COMMENT '媒体安全标识，由响应层生成访问地址',
   `media_mime` VARCHAR(100) NULL COMMENT '服务端识别的媒体MIME',
   `media_size` BIGINT UNSIGNED NULL COMMENT '媒体文件字节数',
   `media_duration` INT UNSIGNED NULL COMMENT '语音或视频时长，单位秒',
   `media_width` INT UNSIGNED NULL COMMENT '图片或视频宽度',
   `media_height` INT UNSIGNED NULL COMMENT '图片或视频高度',
   `client_message_id` VARCHAR(64) NOT NULL COMMENT '客户端幂等消息编号',
   `delivered_time` DATETIME NULL COMMENT '接收方客户端确认送达时间',
   `read_time` DATETIME NULL COMMENT '接收方读取时间',
   `create_time` DATETIME NOT NULL COMMENT '发送时间',
   PRIMARY KEY (`id`),
   UNIQUE KEY `uk_kefu_message_client` (`conversation_id`, `client_message_id`),
   KEY `idx_kefu_message_conversation_id` (`conversation_id`, `id`),
   KEY `idx_kefu_message_undelivered` (`conversation_id`, `delivered_time`, `sender_type`),
   KEY `idx_kefu_message_unread` (`conversation_id`, `read_time`, `sender_type`),
   KEY `idx_kefu_message_member_sender` (`sender_member_id`),
   KEY `idx_kefu_message_user_sender` (`sender_user_id`),
   CONSTRAINT `fk_kefu_message_conversation`
      FOREIGN KEY (`conversation_id`) REFERENCES `kefu_conversation` (`id`)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT `fk_kefu_message_member_sender`
      FOREIGN KEY (`sender_member_id`) REFERENCES `member` (`id`)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT `fk_kefu_message_user_sender`
      FOREIGN KEY (`sender_user_id`) REFERENCES `user` (`id`)
      ON UPDATE RESTRICT ON DELETE RESTRICT,
   CONSTRAINT `chk_kefu_message_sender` CHECK (
      (`sender_type` = 1000 AND `sender_member_id` IS NOT NULL AND `sender_user_id` IS NULL)
      OR (`sender_type` = 2000 AND `sender_member_id` IS NULL AND `sender_user_id` IS NOT NULL)
      OR (`sender_type` = 9000 AND `sender_member_id` IS NULL)
   )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT '客服聊天消息';


# 为会话最后消息增加外键；放在消息表创建后执行以解决建表依赖顺序
ALTER TABLE `kefu_conversation`
   ADD CONSTRAINT `fk_kefu_conversation_last_message`
   FOREIGN KEY (`last_message_id`) REFERENCES `kefu_message` (`id`)
   ON UPDATE RESTRICT ON DELETE RESTRICT;


# 全平台快捷回复表：所有具有客服权限的后台用户共用
CREATE TABLE `kefu_quick_reply` (
   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '快捷回复ID',
   `title` VARCHAR(60) NOT NULL COMMENT '短语标题',
   `content` TEXT NOT NULL COMMENT '文字和表情回复内容',
   `orderby` INT NOT NULL DEFAULT 0 COMMENT '排序值，数值越小越靠前',
   `status` SMALLINT NOT NULL COMMENT '状态（1000启用，9000停用）',
   `create_time` DATETIME NOT NULL COMMENT '创建时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   KEY `idx_kefu_quick_reply_status_order` (`status`, `orderby`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT '客服快捷回复';
