# 多彩种接入验收（2026-10-19）

本次范围：在现有加拿大28页面之外增加用户指定的6个彩种，接入固定采集地址、数据库存储、后台采集状态、前台切换及结果/走势/统计。加拿大28原本的本地预测保留。

后续布局调整：按用户要求，彩种切换已统一移动到广告下方。加拿大28和新增彩种页均实测广告展开/收起后切换栏可见，7个链接正常；390px手机宽度无页面横向溢出，加拿大28四标签切换无JS错误。PHP视图语法、参考资源重建与JS语法检查通过。最新截图 `navigation-below-ads-canada.png` 和 `navigation-below-ads-racing.png` 按项目规范仅保留在本地，此前已归档截图保留作历史记录。

## 实际采集结果

**13:07复验：澳洲幸运5、澳洲幸运10均已恢复。** 原地址返回HTTP 200和10条真实记录，字段为 `expect/opencode/opentime`。本站已入库，采集错误已清除。复验时幸运5最新51360614期（2,6,1,4,0），幸运10最新21369514期（01,04,07,02,03,09,08,10,06,05），开奖时间均为2026-10-19 13:03:40。无需修改采集适配代码。

数据库只读接口快照见 [恢复数据](australia-recovery.json)，六项结果/走势/统计页面检查见 [浏览器核对](australia-browser-checks.json)。截图 `australia5-restored.png`、`australia10-restored.png` 仅保留在本地。后台持续采集已自动接收数据；单次幸运10再次执行接收10期、新增0期，符合重复入库保护。幸运5手动触发遇到正在运行的同彩种任务被互斥锁跳过，已通过成功采集时间、真实入库结果和页面核对确认自动任务正常。

09:28 北京时间快照见 [collection-snapshot.json](collection-snapshot.json)。采集常驻进程继续运行，数字会增长。

| 彩种 | 前台最近记录数 | 验收状态 |
| --- | ---: | --- |
| 加拿大28 | 100（查询上限） | 原有采集、四标签和本地预测正常 |
| 澳洲幸运5 | 0 | HTTP 200，响应内容为空；等待上游有效数据 |
|幸运飞艇 | 10 | 从空响应恢复后自动入库，最新上游时间为2026-10-19 04:04 |
| 急速赛车 | 29 | 真实数据已入库，号码顺序与上游一致 |
| 急速时时彩 | 19 | 真实五位数据已入库，重复数字合法 |
| 急速飞艇 | 18 | 真实十名数据已入库，重复名次被拒绝 |
| 澳洲幸运10 | 0 | HTTP 200，响应内容为空；等待上游有效数据 |

上述09:28快照中的澳洲幸运5和澳洲幸运10保留用户指定的 `nums=1`，当时因空响应未完成真实数据端到端验收。该限制已在13:07复验中解除，见本节恢复记录；原快照保留为历史证据。

## 自动验证

- 领域与前台服务测试：26 tests / 353 assertions，通过。
- 后台实际 Controller 测试：13 tests / 40 assertions，通过。
- 共39 tests / 393 assertions。
- PHPStan level 0：common lottery + frontend models/controllers、后台 draw、console portal 三组均通过。
- PHP语法检查51个文件通过；`node --check frontend/web/js/portal-games.js` 通过。
- 真实HTTP校验：未知彩种、数组参数、路径输入均返回400。
- 迁移 `m261019_000001_add_portal_games` 已执行，保留原有开奖数据。

新增用例覆盖7个来源白名单、两种上游协议、空响应、错误彩种标识、号码数量/范围、赛车重复名次、相同期号跨彩种隔离、前导零标准化、幂等重试、单源失败不影响其他来源、故障恢复、隐藏彩种不公开、前台按总和/冠亚和计算、后台权限/CSRF/POST限制与7个采集按钮渲染。测试数据在事务中回滚，正常测试不访问实时上游。

## 浏览器逐项检查

| 检查 | 结果 / 证据 |
| --- | --- |
| 6个新增彩种切换 | 全部可进入正确页面，选中导航与标题匹配 |
| 每种结果 / 走势 / 统计 | 18项均已点击，见 [switching-checks.json](switching-checks.json) |
| 空来源 | 三个标签均显示等待数据，不显示假号码 |
| 赛车走势第10名 | 位置选择更新命中列，首条显示原结果第10名 |
| 时时彩个位统计 | 已显示16期的频次和为16，见 [mobile-check.json](mobile-check.json) |
| 20 / 50 / 100期 | 当时27条记录分别显示20 / 27 / 27，见 [sample-limits.json](sample-limits.json) |
| 收起 / 展开广告 | 广告区域隐藏/恢复，按钮文字和aria-expanded同步 |
| 刷新 | 读取本站接口成功；选中位置和标签不变 |
| 加拿大28四标签 | 结果、走势、统计、预测仍可用，见 [canada-regression.json](canada-regression.json) |
| 手机390×844 | 页面不产生横向溢出；当前10个号码完整显示，历史表内部可横向滚动 |
| 桌面1440×1000 | 导航、共用横幅、二维码、广告、结果区域正常 |
| 控制台 | 本次检查没有JavaScript error |

本次新增页面使用原站的基础字体、颜色和后台配置的图片/链接，新增彩种按不同号码结构渲染。不是对参考站不存在的六套页面进行像素一致性声明。原加拿大28逐页比对报告保留在 [前次验收](../2026-10-18/README.md)。

截图：

- [手机赛车结果](mobile-racing.png)
- [手机走势](mobile-trend.png)
- [手机时时彩统计](mobile-statistics.png)
- [手机等待采集状态](mobile-waiting.png)
- [桌面赛车页面](desktop-racing.png)

## 代码与运行方式

- `common/models/lottery/FeedCatalog.php`：七个来源及规则白名单。
- `FeedGateway.php` / `CollectionModel.php` / `CollectionState.php`：协议验证、采集隔离、真实入库和故障状态。
- `DrawModel.php`：手工/采集统一校验赛车号码互不重复。
- `console/controllers/portal/CollectController.php` 与同域Form/Service：全部或单源采集，持续重试与退出码。
- `admin/models/portal/draw`、对应Controller/View：独立来源按钮和采集状态面板，沿用原权限。
- `frontend/models/GameForm.php` / `GamePortalService.php`、公开Controller、`views/site/game.php`、`portal-games.js/css`：仅查询数据库的多彩种界面。

单次全量：`php console/index.php portal/collect`。单次单源：`php console/index.php portal/collect jsssc`。常驻全部：`php console/index.php portal/collect/watch 5`。5秒为整轮结束后的间隔。网络请求逐源有界，一源失败继续其他来源并在下轮重试。

后台入口 `portal/draw/index` 可查看各来源最近尝试、最近成功、最新开奖和错误，并分别手动采集。其他角色继续需要已有 `portal/draw/collect` 权限，不新增隐式授权。后台UI渲染和提交边界通过实际Controller测试，本次未使用账户绕过登录进行浏览器验收。

本次仅在本地开发实例执行；未部署到正式服务器，也未配置操作系统开机自启。当前本地服务与采集进程结束后，需要按部署说明重新启动。新增六种页面当前提供开奖、走势和统计，未套用加拿大28预测算法。默认横幅、登录与广告链接共用后台站点配置。
