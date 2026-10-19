# 开奖门户数据库安装

`portal.schema.mysql.sql` 是截至 `m261019_000002_add_portal_comments` 的结构快照，包含全部 7 张 `portal_*` 表、66 个字段的中文注释及主键、唯一键、查询索引和外键。使用 InnoDB、utf8mb4 和本目录通用的 `utf8mb4_unicode_ci` 排序规则，不携带本机自增序号。

`portal.data.mysql.sql` 初始化 7 个彩种、站点默认配置、开奖门户菜单及 23 个权限路由，并记录对应的 6 条 Yii 迁移。默认外观来自 `frontend/config/portal.php` 的仓库默认值；SQL 不读取部署环境变量。导入后在后台配置自己的站点、登录地址和广告。

这两份文件不包含实际开奖记录、预测、公告、采集状态、后台账号、角色授权或数据库连接密码。开奖结果由采集任务持续写入。

## 使用 SQL 安装

适用于目标数据库尚未安装开奖门户的情况。先完成通用后台基础库的安装，确保 `category`、`module` 表可用；全新后台库使用既有的 `system.schema.mysql.sql` 和 `system.data.module.sql` 初始化基础结构及菜单。

1. 备份目标库，核对所选数据库。在项目根目录启动 MySQL 客户端，使用 `-p` 交互输入密码：

   ```text
   mysql --default-character-set=utf8mb4 -u 数据库用户 -p 数据库名
   ```

2. 顺序导入，也可以在数据库管理工具中按同一顺序执行文件：

   ```sql
   SOURCE data/portal.schema.mysql.sql;
   SOURCE data/portal.data.mysql.sql;
   ```

3. 检查初始化结果：

   ```sql
   SELECT code, name, enabled FROM portal_game ORDER BY sort_order, id;
   SELECT id, website, title FROM portal_settings;
   SELECT url FROM module WHERE url LIKE 'portal/%' ORDER BY url;
   SELECT version FROM migration WHERE version LIKE '%portal%' ORDER BY version;
   ```

   应有 7 个彩种、ID 为 1 的站点配置、23 条门户权限及 6 条门户迁移记录。普通后台角色需按需要分配权限。

4. 核对控制台实际连接的数据库后，启动采集：

   ```text
   php console/index.php portal/collect
   php console/index.php portal/collect/watch 5
   ```

结构文件使用普通 `CREATE TABLE`，已存在同名表时会报错，避免覆盖历史数据。导入报错时立即停止并解决原因，不要使用忽略错误的强制导入。初始化数据文件可以重跑：仅补充缺少的彩种、配置和路由，不覆盖已有名称、登录链接、广告或角色授权。

## 使用 Yii 迁移安装

也可以不导入上述 SQL，直接使用项目现有迁移完成结构、配置、彩种及权限初始化：

```text
php console/index.php migrate/up --interactive=0
```

两种安装方式选择一种即可。完整 SQL 安装会写入匹配的迁移记录，之后运行 `migrate/up` 时会跳过这 6 条已安装的门户迁移，继续处理未来迁移。不要只导入结构文件后立即运行迁移，否则会因重复建表失败；应接着导入初始化数据文件。

已经通过迁移安装的现有数据库无需再次导入 SQL，执行 `migrate/up` 应用新增的 `m261019_000002_add_portal_comments` 即可补齐表和字段注释。该迁移保留原字段类型、默认值、索引、外键和业务数据。后续结构变更以 `console/migrations` 为依据，并同步更新这份安装快照。

## 环境与数据迁移

前台入口读取环境变量 `YII_ENV`，默认 `dev`；当前控制台入口根据 PHP 的 `is_prod_mode` / `is_test_mode` 扩展选择 `prod` / `test`，未加载时使用 `dev`。仅设置环境变量不会切换当前控制台入口。执行迁移或采集前，核对对应 `common/config/main-<环境>.php` 的有效数据库配置，避免操作错误的实例。

已有开奖记录和后台广告配置需要单独备份、迁移数据库；上传图片还需迁移 `common/runtime/portal-images`。提交代码和这些安装 SQL 不会同步当前本机的业务数据。

## 本次安装验证

2026-10-19 在 MySQL 8.0.29 上使用随机前缀的独立测试表完成真实 SQL 导入，105 项检查通过：结构、索引、外键与迁移后的实际表一致，66 个字段均有中文注释，7 个彩种及23条路由初始化正确，站点默认值和广告JSON有效。重跑初始化保留自定义配置、已有开奖记录及其他迁移记录；重复期号和无效彩种外键被数据库拒绝，Yii 能识别已导入的6条门户迁移。测试表已清理。

注释迁移另行验证了重复执行及字段类型、默认值、自增属性、索引、外键保持不变，并已在本地开发数据库执行。SQL 的排序规则使用本目录通用值，未在其他 MySQL 版本或正式服务器执行验收。
