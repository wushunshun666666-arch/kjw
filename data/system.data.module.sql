# 数据库表结构初始化脚本
set names utf8;

# 所有模块类别
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10000000,'所有模块',1000,0,0,0, now(),now(),0);

# 模块类别-主页
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10001000,'主页',1000,10000000,1,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10001010,'我的桌面',1000,10001000,2,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10001020,'我的信息',1000,10001000,2,0, now(),now(),0);

# 模块类别-系统管理
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10002000,'系统管理',1000,10000000,1,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10002010,'组织架构和用户管理',1000,10002000,2,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10002020,'角色管理',1000,10002000,2,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10002030,'模块管理',1000,10002000,2,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10002040,'可登录IP管理',1000,10002000,2,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10002060,'日志查询',1000,10002000,2,0, now(),now(),0);

# 模块类别-消息中心
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10003000,'消息中心',1000,10000000,1,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10003001,'站内消息',1000,10003000,1,0, now(),now(),0);

# 模块类别-会员管理
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10004000,'会员管理',1000,10000000,1,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10004010,'会员管理',1000,10004000,2,0, now(),now(),0);

# 模块类别-在线客服
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10005000,'在线客服',1000,10000000,1,0, now(),now(),0);
INSERT INTO `category` (id, name, type, parent_id, level, orderby, create_time, update_time, status) VALUES (10005010,'客服管理',1000,10005000,2,0, now(),now(),0);

# 模块-主页
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10001010,'我的桌面','desktop/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10001020,'我的信息','site/profile','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10001020,'修改密码','site/modify-password','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10001020,'反馈问题','site/feedback','',now(),now());

# 模块-系统管理-组织架构和用户管理
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'用户列表','system/user/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'用户列表','system/user/list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'查看用户详细信息','system/user/detail','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'新增用户','system/user/add','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'修改用户','system/user/modify','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'重置密码','system/user/reset-password','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'设置用户权限','system/user/set-permission','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'新增部门','system/user/add-department','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'修改部门','system/user/modify-department','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'删除部门','system/user/del-department','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002010,'注销用户','system/user/close','',now(),now());

# 模块-系统管理-角色管理
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'角色列表','system/role/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'角色列表','system/role/list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'查看角色详细信息','system/role/detail','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'新增角色','system/role/add','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'修改角色','system/role/modify','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'设置角色权限','system/role/set-permission','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'查看角色权限','system/role/view-permission','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'查看角色用户','system/role/user','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'查看角色用户','system/role/user-list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002020,'查看角色用户','system/role/delete-user','',now(),now());

# 模块-系统管理-模块管理
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'模块列表','system/module/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'模块列表','system/module/list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'新增模块','system/module/add','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'修改模块','system/module/modify','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'删除模块','system/module/delete','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'查看详细信息','system/module/detail','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'查看详细信息(模块角色列表)','system/module/role-module','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'新增模块类别','system/module/add-category','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'修改模块类别','system/module/modify-category','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002030,'删除模块类别','system/module/delete-category','',now(),now());

# 模块-系统管理-可登录IP管理
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002040,'可登录IP列表','system/allow-login-ip/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002040,'可登录IP列表','system/allow-login-ip/list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002040,'新增可登录IP','system/allow-login-ip/add','',now(),now());

# 模块-系统管理-日志查询
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002060,'用户日志查询','log/userlog/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10002060,'用户日志查询','log/userlog/list','',now(),now());

# 模块-消息中心-站内消息
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10003001,'消息列表','message/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10003001,'消息列表','message/list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10003001,'查看消息','message/read','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10003001,'全部标记为已读','message/mark-all-read','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10003001,'消息通知','message/get-menu-list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10003001,'消息通知','message/get-new-nums','',now(),now());

# 模块-会员管理
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'会员列表','member/member/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'会员列表数据','member/member/list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'查看会员详情','member/member/detail','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'新增会员','member/member/add','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'新增会员校验','member/member/check-add','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'修改会员','member/member/modify','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'修改会员校验','member/member/check-modify','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'冻结会员','member/member/freeze','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10004010,'解冻会员','member/member/unfreeze','',now(),now());

# 模块-在线客服
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'客服工作台','kefu/kefu/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'客户会话列表','kefu/kefu/conversation-list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'查看客服会话','kefu/kefu/conversation','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'查看聊天消息','kefu/kefu/messages','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'主动联系会员','kefu/kefu/initiate','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'领取客服会话','kefu/kefu/claim','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'转交客服会话','kefu/kefu/transfer','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'结束客服会话','kefu/kefu/end','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'发送客服消息','kefu/kefu/send','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'上传客服媒体','kefu/kefu/upload','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'读取客服媒体','kefu/kefu/media','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'更新消息已读','kefu/kefu/mark-read','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'快捷回复管理','kefu/quick-reply/index','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'快捷回复列表','kefu/quick-reply/list','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'新增快捷回复','kefu/quick-reply/add','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'修改快捷回复','kefu/quick-reply/modify','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'启用快捷回复','kefu/quick-reply/enable','',now(),now());
INSERT INTO module (id,category_id,name,url,description,create_time,update_time) VALUES (0,10005010,'停用快捷回复','kefu/quick-reply/disable','',now(),now());
