# 三份环境配置

日常只维护 `common/config` 中的三个文件：

| 文件 | 使用场景 |
| --- | --- |
| `main-dev.php` | 本地开发 |
| `main-test.php` | 测试站与自动化测试，使用独立测试库 |
| `main-prod.php` | 每个正式主站、分站自己的生产配置 |

`main.php` 保留公共框架设置。各入口直接 require 对应环境配置并使用 Yii ArrayHelper 合并，不额外引入环境加载文件。

环境选择保留原有入口方式：前台读取环境变量 `YII_ENV`，默认 `dev`；后台和 `console/index.php` 依据 `is_prod_mode`、`is_test_mode` PHP 扩展选择生产或测试环境，否则为 `dev`；根目录 `yii` 默认 `dev`。入口前已定义 `YII_ENV` 时使用该常量。部署时分别核对 Web 与 CLI 实际选择的环境，不要仅设置环境变量就假定后台与 CLI 也已切换。

Linux CLI 示例：

```bash
php -r 'define("YII_ENV", "prod"); require "console/index.php";' help
php -r 'define("YII_ENV", "prod"); require "yii";' help
```

Windows PowerShell 示例：

```powershell
php -r 'define("YII_ENV", "prod"); require "console/index.php";' help
```

## 新站安装

```text
php data/configure-environments.php
```

该命令从 `data/environment-templates` 生成三份本站文件及随机 Cookie 签名密钥；已存在的文件始终保留。模板不参与运行时加载，不能在模板中填写真实凭据。

每个站点编辑自己的环境文件：

- `components.db`：数据库名、用户、密码。
- `components.cache`：默认文件缓存，各站使用自己的运行目录；改用 Redis 时配置本站独立的命名空间。
- `params.lotteryData`：中心/分站角色与服务器间凭据。
- `applications.admin/frontend`：确有差异时设置对应应用的 Cookie 等选项。

生产环境文件中的分站配置示例：

```php
'params' => [
    'lotteryData' => [
        'mode' => 'client',
        'endpoint' => 'https://draw-center.example.com/index.php',
        'client_id' => 'client_a',
        'token' => '填写中心授权的本站独立密钥',
        'clients' => [],
        'cache_seconds' => 2,
        'stale_seconds' => 1800,
    ],
],
```

中心设为 `mode=center`，`clients` 填客户编号、密钥 SHA-256 摘要及启用状态；独立采集站设为 `standalone`。格式及约束见 [共享开奖说明](portal.shared-data.README.md)。

## 旧站升级

此版本移除了原来被 Git 跟踪的环境文件，改为各站私有文件。**第一次更新前先备份有效配置**，包括 `common/config/main-*.php`、`main-local.php`、`lottery-data-local.php`，以及 `admin/config/main-*.php`、`console/config/main-*.php`；备份应保存在 Web 根目录之外。不要直接拉取后再寻找已删除的旧环境文件。

更新后运行上面的初始化命令，从备份将每个环境原来生效的数据库与缓存填回对应环境文件；原 `main-local.php` 的覆盖值应合并到需要保留该值的环境中。测试环境必须使用自己的测试库，不能把生产数据库直接复制给测试环境。原 `lottery-data-local.php` 的数组放到对应文件的 `params.lotteryData`；原后台/控制台环境差异分别放到 `applications.admin`、`applications.console`。

确认迁移后，旧的 `main-local.php`、`lottery-data-local.php` 不再参与加载，可移到备份目录。保留它们不能继续覆盖新配置。

三份真实环境文件已加入 `.gitignore`，后续 `git pull` 不覆盖本站配置；只提交无密钥的模板和业务代码。检查 `git ls-files common/config/main-dev.php common/config/main-test.php common/config/main-prod.php` 应无输出。禁止使用 `git clean -fdx` 清除私有文件。各站需要分别完成首次迁移，本地项目的修改不会自动部署到服务器。
