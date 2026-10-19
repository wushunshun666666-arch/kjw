# 数据库表结构初始化脚本
set names utf8;


# 系统主题域
CREATE TABLE `department` (
   `id` INT NOT NULL auto_increment COMMENT 'ID',
   `parent_id` INT NOT NULL DEFAULT 0 COMMENT '父部门ID,顶级为0',
   `name` VARCHAR(30) NOT NULL COMMENT '部门名称',
   `status` SMALLINT NOT NULL COMMENT '状态(1000正常，9000已删除)',
   `description` VARCHAR(255) COMMENT '部门说明',
   `create_time` DATETIME NOT NULL COMMENT '添加时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin COMMENT '部门';

CREATE TABLE `user` (
   `id` INT NOT NULL auto_increment COMMENT 'ID',
   `username` VARCHAR(20) NOT NULL COMMENT '用户名',
   `password_hash` CHAR(60) NOT NULL COMMENT '登录密码',
   `name` VARCHAR(20) NOT NULL COMMENT '姓名',
   `department_id` INT NOT NULL DEFAULT 0 COMMENT '所属部门ID',
   `tel` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '固定电话',
   `mobi` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '手机号码',
   `email` VARCHAR(90) NOT NULL COMMENT 'Email',
   `status` SMALLINT NOT NULL COMMENT '状态(1000有效，1100冻结，8000不可登录，9000已注销)',
   `timeout` SMALLINT NOT NULL COMMENT '登录超时时间',
   `note` VARCHAR(255) COMMENT '备注',
   `create_time` DATETIME NOT NULL COMMENT '添加时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   UNIQUE KEY (`username`),
   KEY (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin COMMENT '后台用户';

CREATE TABLE `role` (
   `id` INT NOT NULL AUTO_INCREMENT COMMENT '角色ID',
   `name` VARCHAR(30) NOT NULL COMMENT '角色名称',
   `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '描述',
   `create_time` DATETIME NOT NULL COMMENT '创建时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   UNIQUE KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT '角色';

CREATE TABLE `user_role` (
   `id` INT NOT NULL AUTO_INCREMENT,
   `user_id` INT NOT NULL,
   `role_id` INT NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY (`role_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT '用户角色';

CREATE TABLE `category` (
   `id` INT NOT NULL auto_increment COMMENT '类别ID',
   `name` VARCHAR(60) NOT NULL COMMENT '类别名称',
   `type` SMALLINT NOT NULL COMMENT '类别类型(模块类别1000)',
   `parent_id` INT NOT NULL COMMENT '父类别ID',
   `level` SMALLINT NOT NULL COMMENT '类别级别',
   `orderby` INT NOT NULL COMMENT '类别排序',
   `status` SMALLINT NOT NULL COMMENT '状态(0正常，1系统保留类别，2未分类)',
   `create_time` DATETIME NOT NULL COMMENT '创建时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   KEY (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin COMMENT '类别信息';

CREATE TABLE `module` (
   `id` INT NOT NULL auto_increment COMMENT '模块ID',
   `category_id` INT NOT NULL COMMENT '模块类别',
   `name` VARCHAR(60) NOT NULL COMMENT '模块名称',
   `url` VARCHAR(225) NOT NULL COMMENT '模块url',
   `description` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '模块描述',
   `create_time` DATETIME NOT NULL COMMENT '创建时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   UNIQUE KEY (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin COMMENT '模块';

CREATE TABLE `role_module`
(
   `id` INT NOT NULL auto_increment COMMENT '角色模块编号',
   `role_id` INT NOT NULL COMMENT '角色编号',
   `module_id` INT NOT NULL COMMENT '模块编号',
   PRIMARY KEY (`id`),
   UNIQUE KEY (`role_id`, `module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin COMMENT '角色模块';

CREATE TABLE `allow_login_ip` (
   `id` INT NOT NULL auto_increment COMMENT '记录ID',
   `ip` VARCHAR(20) NOT NULL COMMENT 'IP',
   `ownership` VARCHAR(60) NOT NULL COMMENT 'IP归属',
   `note` VARCHAR(255) COMMENT '备注',
   `create_time` DATETIME NOT NULL COMMENT '添加时间',
   `update_time` DATETIME NOT NULL COMMENT '更新时间',
   PRIMARY KEY (`id`),
   KEY (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin COMMENT '允许登录管理端IP';

