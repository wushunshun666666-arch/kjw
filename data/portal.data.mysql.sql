-- 开奖门户初始化：先导入 portal.schema.mysql.sql，且 category/module 基础表必须存在。
-- 仅补缺，不覆盖已有配置。默认外观对应 frontend/config/portal.php 的仓库默认值。
-- 不含真实开奖记录、预测、后台账号或角色授权。
SET NAMES utf8mb4;
SET @portal_install_time = CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '+08:00');

-- DDL 位于事务之前；使用 Yii 默认迁移历史表结构。
CREATE TABLE IF NOT EXISTS `migration` (
  `version` varchar(180) NOT NULL COMMENT 'Yii迁移版本标识',
  `apply_time` int DEFAULT NULL COMMENT '迁移应用时间，Unix时间戳秒',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Yii数据库迁移执行历史';

START TRANSACTION;

-- 七个彩种，代码和号码规则与迁移、固定来源白名单一致。
INSERT INTO `portal_game` (`code`, `name`, `description`, `ball_count`, `ball_min`, `ball_max`, `sort_order`, `enabled`, `version`, `create_time`, `update_time`)
SELECT 'jnd28', '加拿大28', '三位原始号码 · 和值 0–27', 3, 0, 9, 0, 1, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `portal_game` WHERE `code` = 'jnd28');

INSERT INTO `portal_game` (`code`, `name`, `description`, `ball_count`, `ball_min`, `ball_max`, `sort_order`, `enabled`, `version`, `create_time`, `update_time`)
SELECT 'az51', '澳洲幸运5', '五位号码 0–9，保留开奖顺序', 5, 0, 9, 10, 1, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `portal_game` WHERE `code` = 'az51');

INSERT INTO `portal_game` (`code`, `name`, `description`, `ball_count`, `ball_min`, `ball_max`, `sort_order`, `enabled`, `version`, `create_time`, `update_time`)
SELECT 'xyft_168', '幸运飞艇', '第1至第10名，号码 1–10 不重复', 10, 1, 10, 20, 1, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `portal_game` WHERE `code` = 'xyft_168');

INSERT INTO `portal_game` (`code`, `name`, `description`, `ball_count`, `ball_min`, `ball_max`, `sort_order`, `enabled`, `version`, `create_time`, `update_time`)
SELECT 'jspk10', '急速赛车', '第1至第10名，号码 1–10 不重复', 10, 1, 10, 30, 1, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `portal_game` WHERE `code` = 'jspk10');

INSERT INTO `portal_game` (`code`, `name`, `description`, `ball_count`, `ball_min`, `ball_max`, `sort_order`, `enabled`, `version`, `create_time`, `update_time`)
SELECT 'jsssc', '急速时时彩', '五位号码 0–9，保留开奖顺序', 5, 0, 9, 40, 1, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `portal_game` WHERE `code` = 'jsssc');

INSERT INTO `portal_game` (`code`, `name`, `description`, `ball_count`, `ball_min`, `ball_max`, `sort_order`, `enabled`, `version`, `create_time`, `update_time`)
SELECT 'jsft_168', '急速飞艇', '第1至第10名，号码 1–10 不重复', 10, 1, 10, 50, 1, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `portal_game` WHERE `code` = 'jsft_168');

INSERT INTO `portal_game` (`code`, `name`, `description`, `ball_count`, `ball_min`, `ball_max`, `sort_order`, `enabled`, `version`, `create_time`, `update_time`)
SELECT 'az10', '澳洲幸运10', '第1至第10名，号码 1–10 不重复', 10, 1, 10, 60, 1, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `portal_game` WHERE `code` = 'az10');

-- 首次安装默认外观。上线前通过后台配置自己的登录链接与广告。
INSERT INTO `portal_settings` (`id`, `website`, `title`, `banner`, `icon`, `show_qrcode`, `landing`, `ad_wx`, `ad_ww`, `ad_icon`, `notice_scroll`, `ads_json`, `version`, `create_time`, `update_time`)
SELECT 1, 'jnd68.com', '官方授权开奖网', '/assets1/banner.jpg', '/assets1/icon.jpg', 1, 'https://cok11.n9pvn12f.lol:3399?t=1790929762', 'https://hzhb05.vip', '', '', '各项福利介绍请看右方公告！！！各项福利介绍请看右方公告！！！各项福利介绍请看右方公告！！！', '[{"cover":"/assets1/ad.gif","link":"https://tfajn.hcjwj.com/app/register.php?site_id=2081&pt=B9C93FB4-B763-B3D9-588E-2163C860FA12","enabled":1}]', 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `portal_settings` WHERE `id` = 1);

-- 按实际类别 ID 关联菜单，不依赖本机固定编号。
INSERT INTO `category` (`name`, `type`, `parent_id`, `level`, `orderby`, `status`, `create_time`, `update_time`)
SELECT '所有模块', 1000, 0, 0, 0, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `category` WHERE `type` = 1000 AND `level` = 0);

SET @portal_parent_id = (SELECT MIN(`id`) FROM `category` WHERE `type` = 1000 AND `level` = 0);

INSERT INTO `category` (`name`, `type`, `parent_id`, `level`, `orderby`, `status`, `create_time`, `update_time`)
SELECT '开奖门户', 1000, @portal_parent_id, 1, 0, 0, @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `category` WHERE `name` = '开奖门户' AND `type` = 1000 AND `parent_id` = @portal_parent_id AND `level` = 1);

SET @portal_category_id = (SELECT MIN(`id`) FROM `category` WHERE `name` = '开奖门户' AND `type` = 1000 AND `parent_id` = @portal_parent_id AND `level` = 1);

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '彩种 index', 'portal/game/index', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/game/index');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '彩种 list', 'portal/game/list', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/game/list');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '彩种 add', 'portal/game/add', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/game/add');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '彩种 modify', 'portal/game/modify', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/game/modify');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '彩种 detail', 'portal/game/detail', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/game/detail');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '彩种 check-add', 'portal/game/check-add', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/game/check-add');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '彩种 check-modify', 'portal/game/check-modify', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/game/check-modify');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '公告 index', 'portal/notice/index', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/notice/index');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '公告 list', 'portal/notice/list', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/notice/list');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '公告 add', 'portal/notice/add', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/notice/add');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '公告 modify', 'portal/notice/modify', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/notice/modify');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '公告 detail', 'portal/notice/detail', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/notice/detail');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '公告 check-add', 'portal/notice/check-add', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/notice/check-add');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '公告 check-modify', 'portal/notice/check-modify', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/notice/check-modify');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '开奖记录 index', 'portal/draw/index', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/draw/index');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '开奖记录 list', 'portal/draw/list', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/draw/list');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '开奖记录 add', 'portal/draw/add', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/draw/add');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '开奖记录 check-add', 'portal/draw/check-add', '开奖门户独立管理权限', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/draw/check-add');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '采集加拿大28', 'portal/draw/collect', '从固定数据源接收最近开奖记录', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/draw/collect');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '站点与广告配置', 'portal/settings/index', '登录链接、广告和站点图片管理', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/settings/index');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '配置字段校验', 'portal/settings/check', '登录链接、广告和站点图片管理', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/settings/check');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '上传门户图片', 'portal/settings/upload', '登录链接、广告和站点图片管理', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/settings/upload');

INSERT INTO `module` (`category_id`, `name`, `url`, `description`, `create_time`, `update_time`)
SELECT @portal_category_id, '预览门户图片', 'portal/settings/image', '登录链接、广告和站点图片管理', @portal_install_time, @portal_install_time
WHERE NOT EXISTS (SELECT 1 FROM `module` WHERE `url` = 'portal/settings/image');

-- 安装完成后登记对应迁移，避免 migrate/up 对已导入表重复建表。
-- 保留其他迁移记录，不使用 migrate/mark 跳过无关迁移。
INSERT INTO `migration` (`version`, `apply_time`)
SELECT 'm000000_000000_base', UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `migration` WHERE `version` = 'm000000_000000_base');

INSERT INTO `migration` (`version`, `apply_time`)
SELECT 'm261018_000001_create_portal_tables', UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `migration` WHERE `version` = 'm261018_000001_create_portal_tables');

INSERT INTO `migration` (`version`, `apply_time`)
SELECT 'm261018_000002_register_portal_collect_permission', UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `migration` WHERE `version` = 'm261018_000002_register_portal_collect_permission');

INSERT INTO `migration` (`version`, `apply_time`)
SELECT 'm261018_000003_create_portal_settings', UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `migration` WHERE `version` = 'm261018_000003_create_portal_settings');

INSERT INTO `migration` (`version`, `apply_time`)
SELECT 'm261018_000004_create_portal_prediction', UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `migration` WHERE `version` = 'm261018_000004_create_portal_prediction');

INSERT INTO `migration` (`version`, `apply_time`)
SELECT 'm261019_000001_add_portal_games', UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `migration` WHERE `version` = 'm261019_000001_add_portal_games');

INSERT INTO `migration` (`version`, `apply_time`)
SELECT 'm261019_000002_add_portal_comments', UNIX_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM `migration` WHERE `version` = 'm261019_000002_add_portal_comments');

COMMIT;
