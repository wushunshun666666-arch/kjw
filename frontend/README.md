# 开奖门户

当前实现基于本仓库的 Yii2 后台，公开前台采用用户指定的 jnd68.com 固定版本资源，接入本站只读数据适配层。

## 功能与入口

- 公开首页：原站收藏栏、横幅、二维码、广告展开/收起、最新号码、下一期倒计时、滚动公告；每 5 秒查询本站数据。
- 原站四标签：结果、走势、统计、预测；预测含大小、单双、组合、杀组。
- 咪牌：刮开当前号码、刷新遮罩、关闭弹层；公告列表、详情及数据由来页面使用原页面模板。
- 最新 100 期从本站已入库记录查询。已有历史查询服务保留，旧页面入口转入对应标签。
- 广告下方的彩种切换包含加拿大28及6个新增彩种。新增页面显示五位数或十名赛车结果，提供按位置走势、频次统计、最近20/50/100期筛选；样本不足时显示实际期数。
- 后台“开奖门户”：彩种新增/修改/发布，开奖记录查询/核实录入/立即采集，公告新增/修改/发布。
- 管理路由：`portal/game/index`、`portal/draw/index`、`portal/notice/index`、`portal/settings/index`。
- 采集命令：`php console/index.php portal/collect`（默认全部7种），指定单种如 `portal/collect jsssc`。
- 持续采集：`php console/index.php portal/collect/watch 5`，按 Ctrl+C 停止；可限制轮数和来源，例如 `portal/collect/watch 5 2 jspk10`。5秒为每轮结束后的等待间隔，不含上游响应耗时。

## 原站复用与配置

`resources/reference` 保存固定资源和校验摘要，`tools/build_reference.py` 生成可发布资源。详细适配说明见该目录的 README。加拿大28展示模板、文字和样式沿用原站资源；新增彩种共用站点配置，按五位数与十名赛车结构展示，并增加统一彩种导航。

后台“开奖门户 → 站点与广告配置”统一管理登录/注册链接、合作联系链接、顶部域名、标题、滚动公告、横幅和图标。广告支持最多 20 条，每条单独上传图片、设置跳转链接、启停、上移/下移和移除。上传完成后点击“保存配置”，刷新前台即可生效；登录二维码由数据库中的登录地址生成。保存空广告列表会移除全部广告。

图片支持 JPG、PNG、GIF 动图、WebP，最大 5 MB。图片存放于 Web 根目录外的 `common/runtime/portal-images`，保留动画，只有配置引用的公开图片才允许访客读取；上传但未保存的图片仅上传者可预览。当前存储不自动删除旧图片，备份配置时须一并备份该目录。PHP 需启用 fileinfo/GD，并设置 `upload_max_filesize >= 5M`、`post_max_size >= 8M`，代理层也需允许相应请求大小。

`config/portal.php` 的外观字段仅作为首次迁移默认值，迁移保留当前参考站素材和链接；之后修改后台即可，无需改代码或环境变量。此入口配置外部会员登录地址，不会创建新的会员注册系统。文件中的 `reference_notices` 是参考公告接入开关；开奖和预测不依赖参考站接口。

预测在采集完成后由本地 `PredictionModel` 生成，保存到 `portal_prediction`，浏览器只读已保存的数据。算法 `weighted-frequency-v1` 使用最近最多 100 期，至少需要 20 期；由新到旧赋予 N、N-1、…、1 的权重，分别汇总大小与单双，得分相同取最新一期的类别。和值 14–27 为大，0–13 为小。组合显示基本组合与反向组合；杀组沿用原模板的“大小 + 反向组合”文字，以实际结果不等于基本组合为命中。权重分数不是概率，也不承诺预测准确率。

每一期只生成一次，保存样本快照、SHA-256、算法版本和生成时间。只有下一期尚未入库且距离预计开奖超过 5 秒时才会生成；旧历史导入、未来时间或样本不足均不补造预测。展示命中情况时，必须确认预测生成时间早于上游提供的开奖时间。启用前的历史预测显示“-”。开奖时间沿用上游，预计间隔为 210 秒。

后台已发布公告优先；没有站内公告时读取参考站公开公告。`LOTTERY_REFERENCE_NOTICES=0` 可关闭参考公告。外部公告 HTML 经净化后展示，站内公告仍为转义的纯文本。

2026-10-18 使用实际参考站进行页面验收，逐页及按钮记录见 `tests/acceptance/2026-10-18/README.md`。业务期号、时间、统计和本地预测以本站数据为准；不会用参考站数据填充缺失历史。

## 当前数据源

所有固定地址统一维护在 `common/models/lottery/FeedCatalog.php`。新增来源均保留用户给出的 `gamename` 与 `nums=1` 参数。

| 彩种 | gamename | 参数 | 号码规则 |
| --- | --- | --- | --- |
| 澳洲幸运5 | az51 | nums=1 | 5个0–9号码，允许重复 |
|幸运飞艇 | xyft_168 | 无 | 10个1–10号码，互不重复 |
| 急速赛车 | jspk10 | 无 | 同上 |
| 急速时时彩 | jsssc | 无 | 5个0–9号码，允许重复 |
| 急速飞艇 | jsft_168 | 无 | 10个1–10号码，互不重复 |
| 澳洲幸运10 | az10 | nums=1 | 同上 |

新来源协议为 `data[].expect/opencode/opentime`，分别映射期号、号码、北京时间。赛车前导零入库时标准化为整数，展示恢复两位数字；号码顺序不会排序。空响应、空列表、错彩种标识、错误号码、重复名次、超出200条的响应均报错。

2026-10-19 实测：急速赛车返回20期，急速时时彩和急速飞艇各10期；幸运飞艇从空响应恢复后返回10期。13:07再次核对时，澳洲幸运5、澳洲幸运10也均返回10期，实际载荷符合 `expect/opencode/opentime` 协议，已由自动采集入库并通过前台结果、走势、统计验证。两条地址仍保留 `nums=1`，但此轮上游实际返回10条。详见 `tests/acceptance/2026-10-19/README.md`。

新增彩种的结果、走势、统计均读取 `portal_draw`。五位数总和23–45为大、0–22为小；赛车显示前两名冠亚和，12–19为大、3–11为小。新页面不假定下一期开奖间隔；原有本地预测仍仅用于加拿大28。

加拿大28：`http://125.77.158.146:9501/Api/getGameData.php?gamename=jnd28`。

已验证响应：顶层 `code=jnd28`，`data` 包含最近20期。映射如下：

| 来源字段 | 站内字段 | 口径 |
| --- | --- | --- |
| draw | issue | 原始期号字符串 |
| results | numbers | 3位0–9号码，保留顺序，以英文逗号分隔 |
| open_time | draw_time | 当前源按北京时间提供，原样保存 |

网络连接超时5秒，总超时15秒，最大响应1MiB，不跟随重定向。`FeedGateway` 通过固定来源白名单取得地址，不接受浏览器传入任意 URL。

同彩种同期号由数据库唯一索引保证唯一。采集和配置编辑通过彩种行锁串行化；相同数据重试成功，不同结果拒绝覆盖。CLI 同机任务另有文件互斥锁。采集按期提交，部分成功后可安全重跑。

各接口返回有限历史窗口；长时间停采后，超出窗口的历史需要上游另行提供历史接口，系统不会补造记录。带 `nums=1` 的两个来源仅按所给地址采集。

采集状态保存到 `portal_collection_state`：最近尝试时间、成功时间、本轮接收/新增数、错误。各来源独立锁定，后台与CLI共享 `common/runtime/portal-collection-locks`；单源故障不会阻断其他来源。全量单次命令存在任何失败时退出码为1，仍保留成功记录；常驻命令逐轮重试。后台状态面板提供7个独立“立即采集”按钮与最近状态。

## 安装与本地启动

数据库沿用 `common/config/main-<环境>.php` 的当前实例配置。前台通过环境变量 `YII_ENV` 选择环境，默认 `dev`；当前控制台根据 PHP 的 `is_prod_mode` / `is_test_mode` 扩展选择环境，否则使用 `dev`，仅设置环境变量不会切换控制台。先在目标环境核对有效数据库配置再执行迁移：

```powershell
php -d opcache.enable=0 -d opcache.enable_cli=0 console/index.php migrate/up --interactive=0
php -d opcache.enable=0 -d opcache.enable_cli=0 console/index.php portal/collect
php -d opcache.enable=0 -d opcache.enable_cli=0 -S 127.0.0.1:8098 -t frontend/web
```

前台本地地址：`http://127.0.0.1:8098/`。后台可以沿用已有站点，也可另开终端：

```powershell
php -d opcache.enable=0 -d opcache.enable_cli=0 -S 127.0.0.1:8099 -t admin/web
```

后台本地地址：`http://127.0.0.1:8099/index.php?r=site/index`。使用已有后台账号；未创建新账号。管理员默认可管理，其他角色需要在系统权限中分配 `portal/*` 对应动作权限，采集需单独授予 `portal/draw/collect`。

本机曾出现 PHP 缓存沿用复制前项目路径，以上开发命令仅对当前进程关闭 OPcache，未修改全局 PHP 设置。

## 数据结构与安全边界

`data` 目录提供完整 SQL 安装文件：[7张表结构](../data/portal.schema.mysql.sql)、[彩种、站点与权限初始化](../data/portal.data.mysql.sql)、[导入顺序和迁移衔接说明](../data/portal.README.md)。新环境可选择 SQL 导入或 Yii 迁移安装；现有已迁移的数据库无需再次导入。

- `portal_game`：代码、名称、号码数量/范围、排序、发布状态、乐观锁版本。
- `portal_draw`：彩种外键、唯一期号、时间、号码、来源；无修改或删除历史结果的 HTTP 入口。
- `portal_notice`：标题、纯文本正文、发布状态、版本。
- `portal_audit`：领域写入审计，操作人来自可信后台/系统身份。
- `portal_prediction`：唯一期号、算法版本、事前生成时间、输入样本快照和摘要、预测分类；无修改/删除入口。
- `portal_settings`：网站公开配置和按序排列的广告 JSON，带乐观锁版本和修改时间。
- `portal_collection_state`：每彩种一行采集状态，失败保留上一次成功时间。

当前数据库原有 `lottery_*` 表属于其他模块，因此本功能采用独立 `portal_*` 表与 `portal/*` 路由，未改写原有开奖记录或权限。六条迁移已在本地实例执行，其中最后一条补全7张表及66个字段的中文注释；迁移不支持自动回滚，防止误删开奖历史、权限关联及维护说明。普通后台角色需授予 `portal/settings/index`、`check`、`upload`、`image` 四项权限后使用配置及上传功能。

已有开奖记录的彩种不得修改代码或号码规则；名称、说明、排序、发布状态仍可修改。新增与修改表单分开，写请求继承原后台认证、路由授权、CSRF、Method限制与访问日志。

## 生产部署与定时采集

将前台域名 Web 根目录指向 `frontend/web`，后台域名沿用 `admin/web`。运行账号需能写入两个应用各自的 `runtime`、`web/assets` 和共享图片目录 `common/runtime/portal-images`；前台进程需可读取共享图片。PHP 需启用 PDO MySQL、cURL、mbstring、intl、fileinfo、GD。

生产配置稳定、随机的环境变量 `LOTTERY_COOKIE_KEY`；未配置时只读前台使用每请求随机签名密钥，不承诺跨请求 Cookie 持久性。配置生产数据库，前台设置 `YII_ENV=prod`，控制台按上面的环境选择方式核对配置，并保持 PHP-FPM 缓存配置与当前发布目录一致。

尚未修改操作系统计划任务。本地预览已启动持续采集进程。生产可用进程管理器托管 `php /绝对项目路径/console/index.php portal/collect/watch 5`，或在现有调度平台每分钟执行一次单次采集命令，保留退出码和日志。前台每5秒轮询的是站内数据库，不能替代上游采集进程。后台按彩种分别点击“立即采集”，执行同一入库用例。

## 验证命令

```powershell
php -d opcache.enable=0 -d opcache.enable_cli=0 -d xdebug.mode=off vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap common/tests/lottery-bootstrap.php common/tests/unit/models/lottery
php -d opcache.enable=0 -d opcache.enable_cli=0 -d xdebug.mode=off vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap admin/tests/portal-bootstrap.php admin/tests/unit/PortalControllerTest.php
php -d opcache.enable=0 -d opcache.enable_cli=0 -d xdebug.mode=off common/tests/portal-upload-smoke.php
```

集成测试读取本地开发配置，测试业务写入均由外层事务回滚。测试覆盖重复/冲突/越界入库、配置版本、历史规则保护、日期筛选、发布隔离、API协议、后台实际渲染、新增修改详情、权限与CSRF保护。多节点真实并发压测、正式域名部署和系统调度尚未执行。

新增适配测试位于 `common/tests/unit/models/lottery/ReferencePortalTest.php`，覆盖预测期号匹配、只读访问不调用外部预测接口、预测不能覆盖号码、缺失预测、隐藏彩种及公告 HTML 净化。`PredictionTest.php` 覆盖算法权重、样本边界、事前生成、过期跳过、不可变记录和幂等性；`PortalQrcodeTest.php` 覆盖短/长登录地址的二维码生成。

`SettingsTest.php` 覆盖数据库配置发布、广告排序/隐藏/移除、并发版本及不安全链接和图片。上传 smoke 测试启动带随机令牌的临时回环 HTTP 服务，验证真实 multipart GIF 上传、类型/体积拒绝、保存后发布与隐藏后不可读，结束时回滚配置并清理测试图片和服务。
