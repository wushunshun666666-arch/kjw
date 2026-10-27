# 中心采集与分站共享开奖数据

本文是部署操作说明，不表示已经修改或部署任何正式服务器。

共享模式让一个中心统一采集、保存开奖结果和生成预测，其他分站通过服务器之间的认证 API 读取这些数据。每个站点仍使用自己的数据库保存广告图片与链接、登录入口、公告、后台账号，以及彩种开启状态和排序。

## 部署模式

| `mode` | 开奖数据来源 | 本地采集、手工录入、预测生成 | 对外共享 API |
| --- | --- | --- | --- |
| `standalone` | 本站数据库 | 保留原有行为 | 不开放 |
| `center` | 中心采集后写入中心数据库 | 由中心执行 | 仅允许已授权客户读取 |
| `client` | 服务端请求中心 API；短时缓存响应 | 拒绝执行 | 不作为共享中心 |

环境文件中未启用共享时默认 `standalone`，已有单站继续按原方式运行。启用 `client` 后，分站不把中心的开奖记录、采集状态或预测写入本地开奖数据库；本地缓存也不能作为独立采集任务的替代入口。

可采用两种服务器布局：

1. **同一服务器上的多个独立站点**：每站使用独立项目目录、站点根目录、数据库、数据库账号、运行目录和私有共享配置。中心与分站使用各自域名；生产环境建议仍使用 HTTPS。仅同机回环测试允许 `http://127.0.0.1:端口/index.php`、`http://localhost:端口/index.php` 或 IPv6 回环地址。
2. **中心与分站位于不同服务器**：各自部署完整项目和数据库，分站通过中心的 HTTPS 域名请求 API。中心可服务多个分站，每个分站分配不同的客户编号和密钥，便于独立撤销。

不能通过让分站连接中心数据库来实现共享，否则广告、账号等站点设置会相互影响。也不要让多个项目共用可写的 `runtime` 或文件缓存目录。

## 安装和数据库

每个新站先按 [portal.README.md](portal.README.md) 安装通用后台和开奖门户，选择完整 SQL 安装或 Yii 迁移安装中的一种，并配置该站自己的有效数据库连接。已有站点保留自己的配置和业务数据，不重新导入建表 SQL。

共享功能不新增数据库表或字段，不需要为本功能执行新的数据库迁移。`data/portal.schema.mysql.sql` 已包含门户表及字段的中文注释，本功能保持该结构快照不变。分站仍需要本地门户基础表保存站点设置和彩种配置，不能因为不采集就省略基础安装。

前台、后台及 CLI 在各自入口直接加载对应环境文件；环境选择保留原有方式，前台读取环境变量，后台及 `console/index.php` 依据 PHP 环境扩展，根目录 `yii` 默认开发环境，也可预先定义 `YII_ENV` 常量。核对生产站点各入口确实选择 `prod`，数据库密码与共享凭据保存在本站的 `common/config/main-prod.php` 中。三个环境的完整说明与旧站升级步骤见 [环境配置说明](environment.README.md)。

### 同一仓库部署多个站点

每个独立站点使用自己的 Git 工作目录。执行 `php data/configure-environments.php` 创建三份环境文件，在 `common/config/main-prod.php` 中填写本站数据库和 `params.lotteryData`。前台、后台和 CLI 使用同一份生产配置。模板使用本站 FileCache；若改用 Redis，须为各站设置独立命名空间。

三份 `common/config/main-dev.php`、`main-test.php`、`main-prod.php` 已加入 `.gitignore`，各站真实配置不提交。旧站首次升级必须先按环境配置说明备份并迁移，后续更新才可直接执行 `git pull --ff-only origin main`。不要使用 `git clean -fdx`，也不要用模板覆盖已有配置。

没有所选环境文件时启动会报错，不会连接其他环境的默认库；新分站先完成本站配置再开放访问。

## 私有共享配置

共享配置统一写入对应环境文件的 `params.lotteryData` 数组，不再使用独立的 `lottery-data-local.php`。限制环境文件的读取权限为运行 PHP 的系统用户或专用组，禁止通过备份下载、静态目录映射等方式公开。下文配置示例的数组均是 `params.lotteryData` 的值，不能用它替换整个环境文件。

| 配置项 | 用途 |
| --- | --- |
| `mode` | `standalone`、`center` 或 `client` |
| `endpoint` | 分站填写中心完整入口，例如 `https://draw-center.example.com/index.php`；不带查询参数、片段或 URL 用户密码 |
| `client_id` | 分站身份，1～64 位英文字母、数字、下划线或连字符 |
| `token` | 分站服务器保存的独立密钥，32～128 位字母、数字或 `. _ ~ -` 字符；建议使用 32 个随机字节的十六进制表示 |
| `clients` | 中心客户映射；每项包含 64 位小写 SHA-256 `token_hash` 与布尔 `enabled` |
| `cache_seconds` | 新鲜缓存秒数，允许 0～2，默认 2 |
| `stale_seconds` | 网络故障时成功响应允许保留的总时长，默认 1800 秒；从该响应成功获取的时间起算 |

非回环地址必须使用 HTTPS，并确保中心证书有效。`endpoint` 是中心前台的 `index.php`，不是上游采集地址、后台登录地址，也不附带 `?r=...`。

### 为每个分站创建独立凭据

下面是 Linux 操作模板，请替换实际 PHP 命令、专用目录和运行组；这些命令不会在本文编写时执行。应在分站服务器上生成明文密钥，仅将摘要传给中心。不要将明文密钥作为命令参数、URL、日志或浏览器配置。

```bash
install -d -m 700 /etc/lottery-sharing
umask 077
php -r '$token = bin2hex(random_bytes(32)); file_put_contents("/etc/lottery-sharing/client-a.token", $token); file_put_contents("/etc/lottery-sharing/client-a.sha256", hash("sha256", $token));'
```

通过可信管理通道把 `client-a.sha256` 的内容交给中心管理员。分站的 PHP 运行用户必须能够读取 `client-a.token`；可使用专用组授予目录遍历与文件只读权限，不要设置为所有用户可读写。中心只保存摘要，无需保存分站的明文密钥。Windows 部署同样使用 Web 根目录外的私有文件，并只向 PHP 服务账号授予读取权限。

中心可以先以空客户列表启用，不需要提前创建分站或生成密钥。此配置保留中心现有采集和站点功能，但尚未授权任何分站：

```php
<?php
return [
    'mode' => 'center',
    'clients' => [],
    'cache_seconds' => 2,
    'stale_seconds' => 1800,
];
```

确定具体分站后，手工把该客户摘要加入中心 `clients`，例如将上面的空数组替换为：

```php
'clients' => [
    'client_a' => [
        'token_hash' => '替换为该分站密钥的64位小写SHA-256摘要',
        'enabled' => true,
    ],
],
```

上述摘要占位文字不能直接使用；配置校验会拒绝无效摘要。增加第二个分站时创建新的客户编号和密钥，不复用已有客户的 token。程序不会自动创建共享客户或后台账号；启用中心并不表示分站已经接入。

分站 `params.lotteryData` 示例：

```php
<?php
return [
    'mode' => 'client',
    'endpoint' => 'https://draw-center.example.com/index.php',
    'client_id' => 'client_a',
    'token' => trim(file_get_contents('/etc/lottery-sharing/client-a.token')),
    'cache_seconds' => 2,
    'stale_seconds' => 1800,
];
```

中心配置的 `clients` 键必须与分站 `client_id` 完全一致，`token_hash` 必须是分站实际 token 字符串的 SHA-256 摘要。若轮换密钥，生成新 token 并同步替换中心摘要和对应分站环境文件的值；不要把明文 token 写入 `frontend/config/portal.php`、数据库广告设置或前端 JavaScript。

## 只在中心运行采集任务

中心的 PHP CLI 必须连接中心数据库，并加载 `mode=center` 的私有配置。在中心项目根目录，可先单次采集确认结果：

```bash
php console/index.php portal/collect all
```

常驻采集命令为每 5 秒一轮、持续运行、采集全部固定来源：

```bash
php console/index.php portal/collect/watch 5 0 all
```

生产可使用 systemd，下面模板中的项目路径、PHP 路径和用户需按真实服务器调整；如 CLI 需要指定 `php.ini`，在 PHP 可执行文件后加 `-c /实际路径/php.ini`。

```ini
[Unit]
Description=Lottery center collector
After=network-online.target
Wants=network-online.target

[Service]
Type=simple
User=www
Group=www
WorkingDirectory=/srv/lottery-center
ExecStart=/usr/bin/php /srv/lottery-center/console/index.php portal/collect/watch 5 0 all
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

保存为 `/etc/systemd/system/lottery-center-collector.service` 后执行：

```bash
systemctl daemon-reload
systemctl enable --now lottery-center-collector.service
systemctl status lottery-center-collector.service
journalctl -u lottery-center-collector.service -n 50 --no-pager
```

停止并取消开机启动：

```bash
systemctl disable --now lottery-center-collector.service
```

分站不要创建或运行上述采集服务。把已有单站改为 `client` 前，先确认该站现有采集服务、宝塔计划任务、cron 或 Supervisor 作业的真实名称，只停止该分站对应作业。例如已有服务叫 `该分站采集服务.service` 时：

```bash
systemctl disable --now 该分站采集服务.service
```

不要在同机多站部署时误停中心任务。代码会拒绝分站的采集、手工开奖写入和预测生成，但部署时仍应移除重复启动任务，避免进程管理器持续重启失败的命令。站点广告、登录入口、公告及彩种显示配置仍可由各站管理员独立管理。

## API 与站点展示边界

中心提供两条只读 GET 路由，均通过中心前台 `index.php` 访问：

| 路由 | 参数 |
| --- | --- |
| `shared-data/latest` | `game`，固定彩种代码 |
| `shared-data/history` | `game`、`page`、`page_size`、`snapshot` |

请求由分站 PHP 服务端发起，认证信息放在 `X-Lottery-Client` 和 `Authorization: Bearer ...` 请求头。浏览器仍请求自己的分站地址，不直接携带共享密钥访问中心。访问日志、反向代理和错误追踪均不得记录这些认证头的值。

中心共享 API 的数据供应不受中心前台某彩种 `enabled` 显示开关影响；中心关闭首页某彩种展示，不代表取消分站对该固定彩种数据的读取。分站前台仍按分站本地的 `enabled` 和排序展示，不会被中心的广告、公告、登录链接或首页排序覆盖。

历史翻页沿用固定 `snapshot`，避免中心新增一期时翻页出现重复或漏行。点击“回到最新”或修改每页期数后重新建立快照。最新开奖与历史分页独立刷新，浏览历史页不会用最新第一页覆盖当前页。

## 缓存、断网和撤销

- 默认成功响应的新鲜缓存为 2 秒，同一分站的重复请求可复用这一短缓存。
- 网络暂时中断时，只能继续使用该请求此前成功获取且尚在 `stale_seconds` 有效期内的缓存，默认上限为获取后 1800 秒。页面明确提示正在显示缓存及真实获取时间；恢复获取新数据后提示隐藏。
- 每个历史请求按彩种、页码、每页数量和快照分别缓存。断网时只能打开曾经成功缓存的对应页面；未缓存页面或缓存已过期时返回明确错误，并保留当前已显示的历史内容，不能伪造结果或用别的页代替。
- 撤销某个分站时，在中心将对应 `clients[client_id].enabled` 改为 `false` 或删除该客户条目。确认中心新请求已加载修改后的配置；若 PHP OPcache 禁止时间戳校验，须按服务器部署方式重新加载 PHP 工作进程。
- 分站随后收到明确的鉴权失败时，不再使用离线旧缓存。已命中的新鲜缓存最多继续使用配置的 2 秒；该限制针对新的数据读取，不会远程清除访客浏览器已经显示或先前保存的内容。中心不可达与明确撤销是不同情况，网络中断本身不能向分站传递撤销通知。

升级或复制站点时不要复制共享缓存和私有凭据到另一分站。每个分站使用独立身份、私有配置和运行目录，避免站点间错误复用缓存。

## 部署后核对

1. 中心单次采集成功、常驻任务唯一运行，中心数据库实际新增期号；分站没有采集任务。
2. 分站前台公共访问正常，七个彩种按本地开关显示；最新号码、下一期倒计时和历史翻页来自中心数据。
3. 分站独立修改广告、登录链接、公告、彩种顺序后，仅影响该分站；中心隐藏某彩种展示不影响分站对应读取。
4. 临时阻断分站到中心的连接，核对已缓存页面显示中断提示、未缓存历史页报错、超过降级有效期不再返回旧缓存；恢复后能读取新数据。
5. 使用专门测试客户验证禁用后鉴权拒绝及短缓存窗口；确认响应、页面源码、浏览器请求和日志中没有共享 token。
6. 分站的采集命令、手工开奖和预测写入入口拒绝执行，本地开奖记录数量不会因共享读取增加。

以上是验收步骤，需在各自实际部署环境执行并记录结果，不能把本说明当作线上验收结论。
