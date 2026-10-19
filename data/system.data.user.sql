set names utf8;

INSERT INTO department(`id`,`parent_id`,`name`,`status`,`description`,`create_time`,`update_time`)
VALUES(1,0,'公司',1000,'',NOW(),NOW());

INSERT INTO department(`id`,`parent_id`,`name`,`status`,`description`,`create_time`,`update_time`)
VALUES(2,1,'平台部',1000,'',NOW(),NOW());

INSERT INTO department(`id`,`parent_id`,`name`,`status`,`description`,`create_time`,`update_time`)
VALUES(3,1,'产品部',1000,'',NOW(),NOW());

INSERT INTO department(`id`,`parent_id`,`name`,`status`,`description`,`create_time`,`update_time`)
VALUES(4,1,'市场部',1000,'',NOW(),NOW());

# 用户
# 增加系统账号，用于后台程序
INSERT INTO user (id, username, department_id, password_hash, name, tel, email, status, timeout, create_time, update_time)
VALUES(100, 'sys', 2, '$2y$13$FLWHRlaRJueRXkdRoC2Mn.T4yW18neJuuPbuLTCvt6CYU8h/twaDC', '系统账号', '', '', 8000, 3600, now(), now());
# 增加管理员帐号
INSERT INTO user (id, username, department_id, password_hash, name, tel, email, status, timeout, create_time, update_time)
VALUES(101, 'admin', 2, '$2y$13$FLWHRlaRJueRXkdRoC2Mn.T4yW18neJuuPbuLTCvt6CYU8h/twaDC', '系统管理员', '', '', 1000, 3600, now(), now());



# 系统管理员角色
INSERT INTO `role`(`id`,`name`,`description`,`create_time`,`update_time`) VALUES(1,'管理员','系统管理员',NOW(),NOW());

TRUNCATE TABLE role_module;
# 为系统管理员角色添加所有模块权限
INSERT INTO role_module(id,role_id,module_id) SELECT 0,1,id FROM module;
