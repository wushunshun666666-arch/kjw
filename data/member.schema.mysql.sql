# 会员域数据库表结构初始化脚本
SET NAMES utf8mb4;


# 会员主体表：保存基础资料、登录状态以及多级推广的直属关系
CREATE TABLE `member` (
   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '会员ID',
   `parent_id` BIGINT UNSIGNED NULL COMMENT '直属推广会员ID，根会员为空',
   `invite_code` VARCHAR(10) NOT NULL COMMENT '唯一推广码',
   `username` VARCHAR(40) NULL COMMENT '备用登录用户名',
   `password_hash` VARCHAR(255) NULL COMMENT '备用登录密码哈希',
   `nickname` VARCHAR(60) NOT NULL DEFAULT '' COMMENT '会员昵称',
   `avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '会员头像地址',
   `mobile` VARCHAR(32) NULL COMMENT '已验证手机号码（含国际区号）',
   `email` VARCHAR(191) NULL COMMENT '已验证Email',
   `status` SMALLINT NOT NULL COMMENT '状态（1000正常，1100冻结）',
   `register_type` SMALLINT NOT NULL COMMENT '注册方式（1000用户名，2000 QQ，2100微信，2200支付宝，3000 Facebook，3100 X，3200 Telegram，4000抖音，4100快手，4200微博，9000后台）',
   `auth_version` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '认证版本，变更后使旧Session和Token失效',
   `last_login_time` DATETIME NULL COMMENT '最近登录时间',
   `create_time` DATETIME NOT NULL COMMENT '创建时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   UNIQUE KEY `uk_member_invite_code` (`invite_code`),
   UNIQUE KEY `uk_member_username` (`username`),
   UNIQUE KEY `uk_member_mobile` (`mobile`),
   UNIQUE KEY `uk_member_email` (`email`),
   KEY `idx_member_status` (`status`),
   KEY `idx_member_create_time` (`create_time`),
   KEY `idx_member_parent` (`parent_id`),
   CONSTRAINT `fk_member_parent`
      FOREIGN KEY (`parent_id`) REFERENCES `member` (`id`)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT '前台会员';

# 会员账户表：与会员保持一对一关系
CREATE TABLE `account` (
   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '账户ID',
   `member_id` BIGINT UNSIGNED NOT NULL COMMENT '会员ID',
   `available_amount` DECIMAL(20,4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '可用数量',
   `frozen_amount` DECIMAL(20,4) UNSIGNED NOT NULL DEFAULT 0.0000 COMMENT '冻结数量',
   `row_version` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '并发版本号',
   `create_time` DATETIME NOT NULL COMMENT '创建时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   UNIQUE KEY `uk_account_member` (`member_id`),
   CONSTRAINT `fk_account_member`
      FOREIGN KEY (`member_id`) REFERENCES `member` (`id`)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT '会员账户';

# 社交登录身份表：与会员保持多对一关系
CREATE TABLE `member_auth_identity` (
   `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '社交身份ID',
   `member_id` BIGINT UNSIGNED NOT NULL COMMENT '会员ID',
   `provider` SMALLINT NOT NULL COMMENT '社交平台（2000 QQ，2100微信，2200支付宝，3000 Facebook，3100 X，3200 Telegram，4000抖音，4100快手，4200微博）',
   `provider_app_id` VARCHAR(100) NOT NULL COMMENT '社交平台应用ID',
   `provider_user_id` VARCHAR(191) NOT NULL COMMENT '社交平台稳定用户ID',
   `union_id` VARCHAR(191) NULL COMMENT '社交平台跨应用统一用户ID',
   `nickname` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '社交平台昵称快照',
   `avatar` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '社交平台头像地址快照',
   `status` SMALLINT NOT NULL COMMENT '绑定状态（1000已绑定，9000已解绑）',
   `last_login_time` DATETIME NULL COMMENT '最近使用该身份登录时间',
   `create_time` DATETIME NOT NULL COMMENT '首次绑定时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   UNIQUE KEY `uk_member_auth_provider_user` (`provider`, `provider_app_id`, `provider_user_id`),
   UNIQUE KEY `uk_member_auth_member_provider` (`member_id`, `provider`, `provider_app_id`),
   KEY `idx_member_auth_member` (`member_id`),
   CONSTRAINT `fk_member_auth_member`
      FOREIGN KEY (`member_id`) REFERENCES `member` (`id`)
      ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT '会员社交登录身份';
