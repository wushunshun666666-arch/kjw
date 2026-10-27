# Yii2 通用后台框架开发规范

本仓库是基于 Yii2 Advanced、AdminLTE 4 和 Bootstrap 5 的后台项目脚手架。本文既是项目说明，也是人工开发、AI 编码、代码审查和新项目复制时的唯一主规范。

> 本文的目标不是描述“历史代码怎样写”，而是规定“现在及以后必须怎样写”。现存代码与本文冲突时，应把现存代码视为待升级代码，不能继续复制。

本文按“执行协议 → 技术边界 → 分层规则 → 页面与组件 → 业务生成流程 → 验收门禁 → Console”组织。AI 不得只搜索关键词后抽取一条规则；必须先执行第 0 节，再读取任务路由表指定的全部章节。

---

## 0. AI 必读执行协议

### 0.1 规则优先级

发生冲突时，按以下顺序执行：

1. 当前任务中用户明确提出的业务目标、目录范围和验收标准。
2. 当前数据库 Schema/迁移、`composer.lock`、实际注册的 Asset 源文件、组件真实方法签名，以及用户已经明确验收的运行行为。这一层提供客观事实，不代表历史文件的代码结构和风格自动成为规范。
3. 本 README 中标记为“必须”“禁止”“统一”的规则。
4. 本 README 的固定模板和同业务域中已经验收的新框架代码。
5. 其他历史代码。

低优先级内容不能覆盖高优先级内容。不得以“旧页面一直这样写”为理由复制旧 DOM、旧依赖或错误的分层。

规范用词：

- **必须 / 禁止 / 统一**：强制要求。
- **应 / 不应**：默认要求，偏离时必须有可验证的技术原因。
- **推荐 / 优先**：首选方案，可按真实业务作有依据的调整。
- **历史兼容**：只允许维持现有行为，新代码禁止继续扩散。

以下八条是全文不可降级的核心约束，AI 即使因上下文限制无法保留全部文档，也必须继续遵守：

| 编号 | 核心约束 |
| --- | --- |
| `SCOPE` | 只处理授权目录和文件，保留用户已有修改、删除和未跟踪文件 |
| `FACT` | Schema、依赖、方法签名、状态和权限必须读取真实来源，禁止猜测 |
| `LAYER` | Controller、Service、Form、LogicModel、Entity、View 各守唯一职责，禁止跨层捷径 |
| `DOMAIN` | 状态迁移、多表写入、事务和并发规则必须进入 LogicModel |
| `VIEW` | View 只渲染传入数据和调用组件，不查询、不引入业务层、不共享 Add/Modify DOM |
| `COMPONENT` | 公共交互先复用 Widget/Helper/`admin.*`，共性问题修公共根因 |
| `JS-BLOCK` | 复杂页面按业务板块拆构造方法，内部 `init()`，实例只创建一次，跨板块只走公开 API |
| `VERIFY` | 没有相应验证证据不得宣称完成，不能以“页面能打开”代替分层、安全和回归验收 |

### 0.2 每个任务的固定执行顺序

AI 或开发者必须按顺序完成：

1. 明确任务范围、业务事实、数据约束、状态流转和验收目标。
2. 执行 `git status`，识别并保留用户已有修改、删除和未跟踪文件。
3. 读取目标域的数据库结构、Entity、LogicModel、Form、Service、Controller、View 和测试。
4. 读取拟调用 Helper、Widget、JavaScript 的真实实现及至少一个已经验收的调用方，不根据名称猜 API。
5. 列出拟修改文件、复用组件、风险点和测试项；需求无歧义时直接继续实施。
6. 按“数据库约束 → Entity → LogicModel → Form → Service → Controller → View → JS → 测试”顺序实现。
7. 共性问题修复公共根因，并检查全部调用方；不得先在业务页增加局部补丁。
8. 执行与风险相称的语法、静态分析、自动测试和浏览器验收。
9. 最终报告行为变化、修改文件、验证结果及尚未消除的风险。

### 0.3 不得猜测的事项

- 字段、类型、长度、默认值、索引和关系：读取 Schema/迁移。
- Widget/Helper 参数：读取方法签名和真实调用方。
- 状态值及迁移：读取 Entity 与 LogicModel；缺失且会改变业务结果时询问用户。
- 权限路由：读取服务端权限配置，不能从菜单是否显示反推。
- 依赖版本：以 `composer.lock` 和实际 AssetBundle 为准。
- 页面视觉：复用公共主题和同类型已验收页面，不自行发明页面级样式。

只读检查能够确定的问题应自行检查。删除数据、修改线上配置、发送外部消息、扩大目录范围等需要新授权的操作必须停止并询问。

### 0.4 当前仓库的默认工作范围

框架升级、后台规范和公共组件任务默认只关注：

```text
admin/
common/
vendor/mylibs/
```

Console 命令任务的合法范围是 `console/` 及其调用的 `common/` 领域代码；不能因为命令从 CLI 运行，就把实现直接写入 `console/` 根目录或绕过既有分层。

除非用户明确指定，禁止扫描、分析或修改以下独立应用，也禁止把它们当成新代码模板：

```text
admin-api/
distribution/
garage-app/
garage-mobile/
m-distribution/
product-mobile/
supplier/
warehouse/
```

这些目录已有修改属于用户资产，不得撤回。用户明确删除的文件（例如数据模板）不得擅自恢复。临时测试 HTML、截图和调试脚本不得提交仓库。

### 0.5 按任务读取规范

AI 必须先读第 0–3 节，再按任务补读：

| 任务 | 必读章节 |
| --- | --- |
| 新增后台 CRUD | 4–7、10–13 |
| 修改 Entity/LogicModel | 3–4、9、12–13 |
| 修改 Controller/Service | 4–5、10–13 |
| 修改 View/表单/列表 | 5–8、12–13 |
| 修改 Widget/Helper | 6–8、12.3–12.4 |
| 修改公共 JS/CSS/布局 | 7–8、12.3–12.4 |
| 新增复杂页面交互、键盘或外部输入流程 | 5–8、11.3、12.3–12.4 |
| 修改缓存/任务/消息/资源 | 3–4、9、12 |
| 新增或修改 Console 命令 | 2–5、9、12、14 |
| 框架或依赖升级 | 1、3、8、9、12 |

任何任务都不得只阅读相似业务页面。相似页面可以说明业务用法，但公共组件的真实实现和本 README 才决定新代码结构。

仓库中不存在可以不加判断地整文件复制的“万能标准文件”。README 点名某个文件作为基准时，只表示参考被点名的能力，例如 `UserService::search()` 的查询形态；该文件中其他历史命名、注释、格式或业务写法不会因此自动成为规范。

### 0.6 AI 生成与修改契约

AI 在修改任何代码前，必须建立以下四份清单；需求明确时不需要等待用户确认，但清单缺失时不得开始生成：

```text
事实清单：表、字段、类型、默认值、索引、关系、状态、权限、输入输出
现状清单：目标域已有 Entity/LogicModel/Form/Service/Controller/View/JS/测试
复用清单：将调用的 Helper/Widget/公共 JS 方法及其真实签名、已验收调用方
变更清单：新增/修改/删除的文件、每个文件的职责、验证方式和风险
```

生成代码时严格遵守：

1. **先找已有入口，再决定新建文件。** 已有同一用例时扩展原类；没有时按第 2.3、10.2、14.11 节创建完整分层，不能把全部实现塞进最先找到的文件。
2. **先读实现，再写调用。** Helper、Widget、父类方法、Form 加载、JSON、Modal、DataTable 等 API 必须以当前源码签名为准，README 示例不得覆盖真实签名。
3. **先完成后端闭环，再生成 View。** 数据约束、领域动作、Form、Service 和 Controller 未确定前，不得先凭页面截图编造状态、字段或接口。
4. **一次只保留一个事实来源。** 状态在 Entity，状态迁移在 LogicModel，输入规则在 Form，查询在 Service，权限在服务端配置，DOM/事件在 Widget 或公共前端模块。
5. **禁止创建“方便但无归属”的文件。** 不得在应用根目录增加业务 PHP/JS/CSS；不得新增 `Utils/Common/Helper/Manager/Handler` 等职责不清的万能类。
6. **禁止顺手重写用户资产。** 发现范围外问题只报告；不得撤回已有修改、恢复用户删除文件或格式化无关文件。
7. **生成的每个方法必须可解释。** 方法级 PHPDoc 写业务目的；方法体通过命名和分层表达步骤，不添加复述代码或局部类型的注释噪音。
8. **完成必须有证据。** 未执行的命令、测试和浏览器场景不得写成已通过；失败或无法运行时说明原因、输出和剩余风险。

AI 最终交付必须按以下顺序报告：

```text
完成结果
关键行为变化
修改文件及各自职责
执行的验证及结果
未覆盖风险（没有则写“无已知未覆盖风险”）
提交与推送结果（仅在用户要求时）
```

### 0.7 AI 任务执行清单与停止条件

AI 在开始生成代码前，必须在工作上下文中形成以下结构。可以简短展示给用户，但不得省略其中事实：

```text
TaskScope
  allowedPaths: 本次允许读取/修改的目录
  forbiddenPaths: 用户明确排除的目录
DomainFacts
  tables: 字段、类型、默认值、索引、关系
  states: 状态值、允许迁移、每个动作前置条件
  permissions: 页面和写动作的服务端路由权限
ImplementationMap
  Entity: 数据事实与语义方法
  LogicModel: 领域动作、事务、Trace、并发
  Form: HTTP/CLI 输入规则
  Service: 查询或应用用例
  Controller: 输入与响应
  View/Widget/JS: 渲染与交互
VerificationMap
  static: 语法、diff、静态分析
  domain: 正常、失败、回滚、并发、权限
  browser: 首次加载、Modal、AJAX、重复初始化
```

遇到以下情况必须停止“编造代码”，先继续只读检查；只读检查仍不能确定时再向用户询问：

- 数据库没有字段或索引依据，却要求生成 Entity/查询/唯一性规则。
- 状态值、允许迁移或权限动作缺失，继续实现会改变业务结果。
- README 示例参数与当前 Helper/Widget/父类真实签名不一致。
- 同一业务动作可能放入两个层，且现有代码无法证明唯一归属。
- 用户要求的目标与当前未提交修改直接冲突，无法安全保留双方变更。

不得因以下原因停止：可以通过读取源码确定；只是文件较多；测试需要先安装依赖；旧页面写法与规范不同。此时应继续检查、按高优先级事实实现并如实报告验证边界。

---

## 1. 技术基线与依赖边界

### 1.1 当前基线

| 能力 | 标准 |
| --- | --- |
| PHP | Composer 约束 `>=8.2`；新项目目标 PHP 8.4+ |
| Yii | `yiisoft/yii2 ~2.0.54`，精确版本以 Lock 为准 |
| Yii Bootstrap | `yiisoft/yii2-bootstrap5 ~2.0.2` |
| 后台主题 | AdminLTE 4.9.1 |
| CSS 框架 | Bootstrap 5.3.8 |
| JavaScript | jQuery 4 + jquery-migrate |
| 模块加载 | SeaJS / CMD |
| 搜索选择 | Selectize 0.15.2 Bootstrap 5 主题 |
| 邮件 | Symfony Mailer |
| 实时消息 | Workerman + 原生 WebSocket |
| 静态资源 | Yii AssetBundle + `assetVersion` |

`composer.lock` 是 Composer PHP 包和 Asset Packagist 包版本的权威来源；浏览器最终运行版本还必须以当前应用实际注册的 AssetBundle、项目覆盖配置和资源文件头为准。例如本仓库 Composer 中虽然存在 `bower-asset/jquery`，后台实际 jQuery 由 `common/config/main.php` 对 `yii\web\JqueryAsset` 的覆盖决定。升级依赖或替换覆盖资源后，必须同步更新 Lock、Asset 配置和本节版本说明。

### 1.2 禁止回退

禁止新增或重新注册：

- Bootstrap 3 及其 `data-toggle`、`data-dismiss`、`pull-left/right` DOM。
- AdminLTE 2 及其 `.box*`、`.main-*`、`.skin-*` DOM。
- SwiftMailer。
- Socket.IO 客户端；WebSocket 只从 View 接收后端配置并连接 Workerman。
- 与现有版本并存的第二套 jQuery、Bootstrap 或 AdminLTE。

### 1.3 Vendor 边界

- PHP 依赖统一由 Composer 管理，`composer.json` 与 `composer.lock` 同步提交。
- 本项目部署方式要求提交 Vendor 时，Vendor 必须与 Lock 一致。
- `vendor/mylibs` 是项目自有框架扩展，允许修改，但必须按公共代码审查和回归。
- `vendor/yiisoft`、`vendor/symfony`、`vendor/twbs` 等第三方包禁止直接修改；应升级依赖、调整配置或在应用/`mylibs` 层扩展。

---

## 2. 总体架构与依赖方向

### 2.1 标准调用链

读取、写入和渲染是三条不同链路，不能混成一个双向依赖图。

HTTP 写入链路：

```text
Request → Controller → Form(load + validate) → Service
        → LogicModel(领域校验 + 事务 + 状态迁移) → Entity → Database
```

HTTP 查询与渲染链路：

```text
Request → Controller → SearchForm(load + validate) → Service → Entity Query
        → Controller → View → Helper/Widget → HTML/JavaScript
```

Console 链路：

```text
CLI → Console Controller → Console Form/Service
    → common LogicModel/Entity/Gateway → Database/External System
```

下层禁止反向依赖上层：

- `common` 禁止依赖 `admin`。
- Entity/LogicModel 禁止读取 Request、返回 Response、输出 HTML/JSON。
- Service 禁止渲染 View 或读取全局请求。
- View 禁止查询 Entity、实例化 LogicModel/Service。

`Form` 不位于 LogicModel 的“下游”，它只承接边界输入；`Entity` 也不得调用 LogicModel。箭头表示运行时调用方向，不表示目录继承关系。

### 2.2 各层唯一职责

| 层 | 负责 | 禁止 |
| --- | --- | --- |
| 数据库 | 类型、非空、默认值、唯一键、外键、并发完整性 | 仅靠应用代码维护唯一性 |
| Entity / ActiveRecord | 表映射、关系、基础验证、枚举、无副作用语义方法、稳定查询 | 跨表流程、事务、消息、HTML、Request |
| LogicModel | 聚合、领域动作、状态迁移、多表事务、领域错误 | 页面跳转、JSON、表单 DOM、请求解析 |
| Form / SearchForm | 请求字段、场景、格式、长度、跨字段输入验证 | 保存实体、开启事务、发送消息 |
| Service | 应用用例、查询分页、Form 到领域参数转换、领域错误转换 | 核心状态规则、HTML/JSON、Request |
| Controller | 输入、验证、调用、响应 | SQL、事务、多表写入、状态常量判断 |
| View | 渲染已传入数据、调用 Helper/Widget、轻量交互编排 | 数据查询、业务计算、领域写入、自造组件 |
| Widget | 可复用 DOM、资源、配置、事件、初始化与销毁 | 具体业务决策 |

同一事实只能有一个所有者，其他层只读取或调用，不能复制：

| 事实/规则 | 唯一所有者 | 其他层的正确用法 |
| --- | --- | --- |
| 字段类型、null、默认值、唯一性 | 数据库 Schema/迁移 | Entity rules 与之保持一致 |
| 状态数值和状态名称 | Entity | Service 取字典，View 调用 getter/predicate |
| 状态是否允许变化 | LogicModel | Service 调用业务动词，Controller 不判断常量 |
| HTTP/CLI 输入格式 | 对应 Form | Controller 只 load + validate |
| 列表查询、分页、排序 | Service | Controller 只接收 ActiveDataProvider |
| 页面可见数据和字典 | Controller/Service 准备 | View 只消费已传变量 |
| 可复用 DOM 和组件事件 | Widget/Helper/公共 JS | View 只传配置并调用公开入口 |
| 页面板块临时交互状态 | 对应 JS 板块实例 | 其他板块只调用公开 API/回调 |
| 路由权限 | 服务端 Authorization 配置 | 菜单/按钮隐藏只改善体验 |

如果一条规则同时出现在两个层，AI 必须保留上述唯一所有者中的实现，删除或改为调用其他副本。不得通过“保持两边一致”维持重复事实。

### 2.3 标准目录

```text
common/models/<domain>/<Entity>.php
common/models/<domain>/<Entity>Model.php

admin/models/<module>/SearchForm.php
admin/models/<module>/AddForm.php
admin/models/<module>/ModifyForm.php
admin/models/<module>/<Module>Service.php
admin/controllers/<domain>/<Entity>Controller.php
admin/views/<domain>/<entity>/index.php
admin/views/<domain>/<entity>/_view_list.php
admin/views/<domain>/<entity>/add.php
admin/views/<domain>/<entity>/modify.php
admin/views/<domain>/<entity>/detail.php
```

`admin/models/<module>` 的目录名已经表达主业务名，目录内类名禁止重复该前缀。唯一例外是该目录的主应用 Service：为了在 Controller 中清晰识别业务入口，保留 `<Module>Service.php`。

```text
admin/models/kefu/
├── KefuService.php              # 主业务 Service，保留 Kefu
├── ConversationSearchForm.php   # 不写 KefuConversationSearchForm
├── QuickReplyForm.php           # 不写 KefuQuickReplyForm
├── QuickReplySearchForm.php
├── QuickReplyService.php        # 子功能 Service 保留 QuickReply，不重复 Kefu
├── SendForm.php
└── TransferForm.php

admin/models/member/
├── MemberService.php
├── AddForm.php
├── ModifyForm.php
└── SearchForm.php
```

规则：

- 主 Form 统一为 `AddForm`、`ModifyForm`、`SearchForm`。
- 子功能类保留子功能语义，如 `QuickReplyForm`、`ConversationSearchForm`，只省略已经由目录表达的 `Kefu/Member` 前缀。
- 主 Service 使用 `<Module>Service`；子功能 Service 使用 `<Feature>Service`。
- 文件名、类名和 PSR-4 大小写必须一致。
- Controller、View PHPDoc、测试和 `use` 语句必须同步采用缩短后的类名。
- `common/models` 的 Entity 和 LogicModel 不适用此省略规则，仍使用完整领域实体名，避免跨应用引用时语义不清。

测试跟随对应层：

```text
common/tests/unit/models/<domain>/<Entity>ModelTest.php
admin/tests/unit/models/<domain>/<Entity>ServiceTest.php
admin/tests/functional/<domain>/<Entity>Cest.php
```

---

## 3. PHP、数据库与安全通则

### 3.1 PHP 代码

新代码按 PSR-12：

- 类名 `PascalCase`，方法/属性 `camelCase`，常量 `UPPER_SNAKE_CASE`。
- 使用 `SomeClass::class`，禁止新增 `className()`。
- 覆盖父类方法时参数和返回类型必须兼容。
- 能明确且不破坏父类、接口及本项目既有调用约定的参数、属性、返回值应声明类型和 `null` 语义；明确规定保留无返回类型的方法（例如 5.1.1 的列表 `search()`）必须通过方法 PHPDoc 写清返回契约。
- 本注释规范适用于整个 PHP 项目，不限于 Controller。Controller、Service、ActiveRecord Entity、LogicModel、FormModel、Query/Repository、Helper、Widget、Behavior、Job、Message、Resource 等类都必须遵守。
- 每个类必须有类级 PHPDoc，说明该类在当前分层中的业务职责；ActiveRecord 还应在类级 PHPDoc 中声明数据库字段和关联魔术属性。
- 每个方法都必须在方法定义处使用方法级 PHPDoc 说明用途，包括 `public`、`protected` 和 `private` 方法；Controller Action、Service 用例、Entity 查询/语义方法、LogicModel 业务动作尤其不得省略。
- PHPDoc 应解释方法的业务目的、重要前置条件和副作用。参数或返回契约不能由类型完整表达时，补充 `@param`、`@return`、数组 shape、可空语义或异常条件；禁止仅把方法名逐字翻译一遍。
- 方法内部能够由赋值、构造函数或返回类型明确推断的局部变量禁止添加 `/** @var ... */` 注释；局部注释只解释无法通过命名和代码结构表达的业务原因，不复述“创建某个 Service”等代码表面含义。
- `use` 只引入当前文件实际使用的类；删除某个 Helper/Widget 调用后必须同时删除对应的无用 `use`。
- 禁止动态属性、隐式全局变量、吞异常和通过关闭错误级别隐藏问题。
- 可空值先归一化再传入字符串函数。

```php
$value = trim((string) ($value ?? ''));
$items = explode('/', (string) ($value ?? ''));
```

禁止：

- 向 `explode()`、`trim()` 等传入 `null`。
- PHP 8.4/8.5 已弃用或无作用 API，例如 `Redis::delete()`、`curl_close()`。
- 把 PHP PCRE 正则直接复制到 JavaScript；两端正则必须分别实测。
- 在 `/u` JavaScript 正则字符组中保留非法转义。

### 3.2 数据库访问

- 新增或修改业务表时必须填写中文表注释和全部字段的中文注释；状态字段写明取值含义，关联字段写明关联对象，时间、金额、数量等字段写明时区或单位。迁移与 `data/` 中的 SQL 结构文件必须同步，验收时检查实际数据库注释，不能只写 PHP 注释。
- 优先使用 ActiveRecord 或 Query Builder；原生 SQL 必须使用命名参数绑定。
- 表名、字段名、排序列和方向只来自代码白名单，禁止使用请求值拼接。
- 列表明确 `select()`；关联展示使用 `with()`/`joinWith()`，禁止 N+1。
- 大数据使用分页、`batch()`、`each()` 或离线任务。
- 金额、数量、比例明确数据库精度；金额计算不得使用不受控浮点数。
- 唯一性必须有数据库 UNIQUE KEY；应用预检查只改善提示。
- 并发敏感更新必须使用旧状态条件、原子 UPDATE、行锁或唯一约束。
- 禁止“先查库存再扣”“查最大编号 + 1”且没有并发保护。

统一查询条件优先使用 `mylibs\yii\db\QueryHelper` 的 `integerRange()`、`region()`、`timeRange()`、`dateRange()`。字段名仍必须来自代码白名单。

### 3.3 输入、输出和敏感信息

- POST、页面弹窗 POST 和写操作启用 CSRF，并限制 HTTP Method。
- 页面动态纯文本默认 `Html::encode()`；富文本按白名单净化。
- 上传同时校验扩展名、MIME、文件头、大小、路径和权限。
- 密码、验证码、Token、Cookie、Authorization、私钥、完整支付报文不得写日志。
- 数据库密码、Redis 密码、AWS Key、微信 Secret、支付密钥、WebSocket Token、PEM 私钥不得提交仓库。
- 历史上提交过的明文密钥视为已泄露，必须轮换，不能只删除文件。
- 外部接口设置连接/读取超时，只重试幂等操作，验证签名、时间戳和重放。

---

## 4. `common` 领域层规范

### 4.1 `common` 的职责

| 目录 | 职责 |
| --- | --- |
| `common/models` | Entity、LogicModel、FormModel、身份和领域事件 |
| `common/caching` | 缓存读取、构建、失效 |
| `common/messages` | 站内、微信、推送消息对象 |
| `common/resource` | 上传参数、临时文件、迁移和资源 URL |
| `common/widgets` | 跨应用共享 UI 组件 |
| `common/validators` | 跨表单格式验证器 |
| `common/jobs` | 可重试后台任务 |
| `common/mail` | 邮件主题、模板参数和视图 |
| `common/log` | 业务日志入口 |
| `common/actions` | 跨应用通用 Controller Action |
| `common/config` | 共享基础配置及环境差异 |

业务类进入域子目录，命名空间与路径严格一致；禁止把新业务类堆在 `common/models` 根目录。

### 4.2 ActiveRecord Entity

Entity 统一继承 `common\models\ActiveRecord`，文件内容依次为：类/字段注释 → 常量 → `tableName()` → `rules()` → `attributeLabels()` → `behaviors()` → relations → 稳定查询 → 枚举名称 → 语义方法。

必须：

1. `rules()` 与数据库类型、长度、null/default、唯一约束一致。
2. `create_time/update_time/status_time` 由 Behavior 生成时设为 `safe`，禁止同时在提交前设为 `required`。
3. 普通时间使用 `timestampBehavior()`；状态时间使用 `timestampBehaviorStatusTime()` 或显式赋值。
4. 新状态使用语义常量，如 `STATUS_EFFECTIVE`；数字式常量名只作历史兼容。
5. relation 用 `getXxx(): ActiveQuery`，返回 Query 而不是已执行数组。
6. 软删除实体的默认定位方法排除删除状态；历史查询使用名称明确的方法。
7. `isXxx()`、`getXxxName()` 是无副作用的实体语义方法。
8. `canXxx()` 只在判断仅依赖自身字段时放 Entity；涉及权限、关联、库存或错误原因时放 LogicModel。
9. Entity 不发送消息、不移动文件、不改余额、不执行跨表业务。
10. 普通保存不得用 `save(false)` 绕过验证；可信领域流程确需使用时必须说明依据。

最小模板：

```php
/**
 * 客户数据实体。
 *
 * @property int $id
 * @property string $number
 * @property string $name
 * @property int $status
 * @property string $create_time
 * @property string $update_time
 */
final class Customer extends \common\models\ActiveRecord
{
    public const STATUS_EFFECTIVE = 1000;
    public const STATUS_FREEZE = 1100;
    public const STATUS_CLOSED = 9000;

    /**
     * 返回客户表名。
     */
    public static function tableName(): string
    {
        return 'customer';
    }

    /**
     * 返回实体字段验证规则。
     */
    public function rules(): array
    {
        return [
            [['number', 'name', 'status'], 'required'],
            [['status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['number'], 'string', 'max' => 20],
            [['name'], 'string', 'max' => 64],
            [['number'], 'unique'],
        ];
    }

    /**
     * 返回实体行为配置。
     */
    public function behaviors(): array
    {
        return [$this->timestampBehavior()];
    }

    /**
     * 按主键查找未关闭的客户。
     */
    public static function findById(int $id): ?self
    {
        return self::find()
            ->andWhere(['id' => $id])
            ->andWhere(['<>', 'status', self::STATUS_CLOSED])
            ->one();
    }

    /**
     * 返回客户状态名称映射。
     *
     * @return array<int, string>
     */
    public static function getStatusNames(): array
    {
        return [
            self::STATUS_EFFECTIVE => '有效',
            self::STATUS_FREEZE => '冻结',
        ];
    }

    /**
     * 返回当前客户状态名称。
     */
    public function getStatusName(): string
    {
        return self::getStatusNames()[$this->status] ?? '未知';
    }

    /**
     * 判断客户当前是否有效。
     */
    public function isEffective(): bool
    {
        return $this->status === self::STATUS_EFFECTIVE;
    }
}
```

### 4.3 LogicModel 与错误契约

跨实体业务统一继承 `common\models\LogicModel`，通常命名为 `XxxModel`。它持有聚合根，并通过 `getById()/getByNumber()` 恢复、`create()` 创建、`getData()` 只读返回实体、业务动词执行状态迁移。

公开写方法顺序固定为：

```text
主体存在 → 当前状态 → 权限/归属 → 业务前提
→ 事务写入主表/明细/流水/Trace → 提交 → 提交后副作用
```

错误契约必须单一：

- 静态工厂/检查：成功返回领域对象或 `true`；可预期失败返回 `common\models\Error`。
- 已恢复聚合的实例动作：成功返回 `true`；失败调用 `setError()/addErrors()` 并返回 `false`。
- `getById()` 查不到返回 `null`，由上层转为业务错误或 404。
- 数据库连接、编程错误等不可预期异常直接向上抛，由统一错误处理记录。
- 同一方法禁止混用字符串、`false`、`Error` 和异常表达同一种业务失败。

### 4.4 LogicModel 事务是强制特例

本项目统一使用 `mylibs\yii\db\Transaction`，不得套用其他项目常见的 Yii 原生事务模板。

```php
/**
 * 创建客户聚合。
 *
 * @param array{name: string} $attributes 已验证的客户属性
 * @return self|Error 创建成功的客户聚合或业务错误
 */
public static function create(array $attributes): self|Error
{
    $transaction = new \mylibs\yii\db\Transaction();

    $entity = new Customer();
    $entity->name = $attributes['name'];
    $entity->status = Customer::STATUS_EFFECTIVE;

    if (!$entity->save()) {
        $transaction->rollback();
        return new Error($entity->getErrors());
    }

    // 其他必须同时成功的写入；每个 Error/false 分支显式 rollback()。

    $transaction->commit();
    return new self($entity);
}
```

必须：

- 一个业务动作只有一个清晰事务所有者，通常是最外层 LogicModel。
- 每个可预期 `Error/false` 返回分支先显式 `$transaction->rollback()`。
- 成功路径只执行一次 `$transaction->commit()`。
- 包装器只提交/回滚自己开启的事务，支持嵌套领域调用。
- 不可预期异常直接传播；包装器析构时回滚仍 active 的自有事务。

严格禁止：

```php
try {
    // ...
} catch (Throwable $exception) {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
    throw $exception;
}
```

原因：项目包装器不公开 `isActive`，也不要求业务代码捕获后原样重抛。禁止把 `try/catch/finally`、Yii 事务闭包或上述模板写成项目标准。

### 4.5 FormModel

HTTP 输入模型统一继承 `common\models\FormModel`：

- POST 用 `loadPost()`，GET 用 `loadGet()`，加载成功后必须 `validate()`。
- AddForm、ModifyForm、SearchForm 分开；共享规则可提取基类，不制造万能 Form。
- `rules()` 定义必填、类型、长度、格式和跨字段输入校验。
- `attributeLabels()`、`attributeHints()` 是字段名和提示的唯一来源。
- 只把验证后的 `getSafeAttributes()` 传给 Service/LogicModel。
- ID、状态、权限、唯一性、并发等领域规则在写入时再次验证。
- 密码、Token、签名、PEM、富文本、文件内容不得被通用递归 `trim()` 破坏。
- Form 禁止 `save()`、事务、消息、Response 和页面跳转。

### 4.6 领域辅助对象

| 类型 | 用途 | 约束 |
| --- | --- | --- |
| `XxxTrace` / `XxxHistory` | 状态操作轨迹 | 只追加；记录主体、前后状态、操作人、时间、原因 |
| `XxxImporter` | CSV/批量导入 | 行解析、验证、错误行号、限制、分批写入独立封装 |
| `XxxStatistics` | 聚合报表 | 只读，返回结构明确的数组/DTO |
| `XxxRule` | 可替换业务规则 | 输入、输出、优先级明确，不读 Request |
| Value Object / DTO | 复杂参数和值语义 | 不用含义不明的自由数组扩散 |
| Yii Behavior | 共享领域事件响应 | 事件明确，不承担不可靠的关键副作用 |
| Sequence | 并发安全编号 | 独立序列/唯一索引，禁止最大值加一 |

### 4.7 身份、状态与并发

- 当前操作人通过 `LogicModel::getIdentity()/getUserId()/getUserType()/getSysUserId()` 取得，不能信任 POST 中的操作人 ID。
- 长驻 Workerman/Queue Worker 每个任务开始前必须设置/重置身份，防止静态 identity 泄漏。
- 状态名集中在 Entity；View/Controller 不维护状态映射和魔法数字。
- 状态更新携带旧状态条件，检查影响行数，防止重复操作。
- Trace、主表、明细、库存/账户流水在同一事务内一致。
- 文件移动、支付、微信、邮件等不可回滚副作用放在提交后；强一致投递使用 Outbox/补偿。

### 4.8 验证器、错误处理与接口边界

可跨业务复用的格式规则写入 `common/validators`，在 Form/Entity 的 `rules()` 中声明。字段按真实语义选择 `CompanyNameValidator`、`FullNameValidator`、`UserNameValidator`、`TelephoneValidator`、`MultiTelephoneValidator`、`IpValidator`、`MultiIpValidator`、`PartsNumberValidator`、`LocationNumberValidator`、`DisplacementValidator` 等，不能用一个宽松正则替代全部规则。

格式验证与领域验证必须分开：验证器检查输入形态；数据库约束和 LogicModel 检查唯一性、权限、库存、余额和状态迁移。

- `common\actions\ErrorAction` 统一转换 Admin AJAX 异常和普通错误页。
- 只有可公开的 `UserException` 等消息可原样展示；其他异常向用户返回通用提示，日志保留堆栈。
- 现有 Admin AJAX 保持 HTTP 200 + `code/message/data` 兼容协议。
- 新 REST/API 使用正确 HTTP 状态码；同一接口禁止混用两种协议。
- `Authorization` 负责服务端路由权限；`AccessLog` 必须递归过滤敏感字段并限制大文本。
- JSON-RPC 仅使用其专用 Controller/Error；关闭 CSRF 时必须有签名、鉴权和防重放。

---

## 5. `admin` 应用层规范

### 5.1 Service

Service 是 Admin 用例入口，负责：

- 列表查询、分页、排序白名单和预加载关系。
- 将已验证 Form 转成领域参数。
- 编排一个或多个领域动作，但不重复开启同一事务。
- 将 `Error`/LogicModel 错误转换为 Controller 可读取的错误。
- 准备 View 需要的枚举、树、URL 和展示配置。

Service 禁止读取 Request、输出 JSON/HTML、决定按钮样式、复制 LogicModel 状态规则或绕过 LogicModel 直接修改受保护字段。

#### 5.1.1 列表 `search()` 固定写法

后台列表查询应保持简洁、可逐行核对，并以 `admin/models/system/UserService.php` 的查询方式为基准：

1. 使用 `$query = Entity::find()` 建立查询。仅展示关联数据且不按关联表筛选/排序时使用 `with()`；需要按关联字段筛选、排序或让关联条件参与主查询时使用 `joinWith()`。两者都不是默认必加项，必须由 `_view_list.php` 的真实取值和查询条件决定。
2. 每个独立的 SearchForm 字段对应一条 `andFilterWhere()`，不得把本来独立的字段擅自合并成一个大范围 `keyword OR` 查询。
3. 树形部门、地区等特殊条件单独使用短 `if`，调用领域层已有的子节点/地区查询方法后追加 `andWhere()`。
4. 没有明确性能证据或字段冲突时，禁止罗列冗长 `select([...])` 字段清单。
5. 只加载列表 View 确实使用的关联；禁止为“可能以后会用”嵌套多层 `with()` 和闭包字段裁剪。
6. Service 方法体不写局部 `@var`，也不写“创建查询”“预加载数据”“返回分页”这类复述代码的行内注释。
7. 为保持当前 Service 基类及现有调用风格，`search()` 不强制追加返回类型声明，在方法 PHPDoc 中使用 `@return ActiveDataProvider` 表达返回契约。
8. 分页必须使用 `$this->getOption('pagesize')`，禁止硬编码 `20`、`30`、`100`。
9. `sort.attributes` 必须显式声明允许排序的字段及必要映射，不能只提供 `defaultOrder`。
10. 中文名称按当前项目数据库规则使用 `CONVERT(<column> using gbk)` 排序；时间等展示字段可映射到稳定主键排序，保持结果稳定。
11. `defaultOrder` 必须使用带表名前缀的真实字段，避免 join 后字段歧义。

标准实现：

```php
/**
 * 查询后台用户列表。
 *
 * @return ActiveDataProvider 用户列表数据提供器
 */
public function search(SearchForm $form)
{
    $query = User::find()->joinWith('department');
    $query->andFilterWhere(['user.id' => $form->id]);
    $query->andFilterWhere(['like', 'user.username', $form->username]);
    $query->andFilterWhere(['like', 'user.name', $form->name]);
    $query->andFilterWhere(['user.status' => $form->status]);

    if (!empty($form->departmentId)) {
        $childIds = Department::getChildIds($form->departmentId);
        $query->andWhere(['in', 'department.id', $childIds]);
    }

    return new ActiveDataProvider([
        'query' => $query,
        'pagination' => [
            'pageSize' => $this->getOption('pagesize'),
        ],
        'sort' => [
            'attributes' => [
                'user.username',
                'user.create_time' => [
                    'asc' => ['user.id' => SORT_ASC],
                    'desc' => ['user.id' => SORT_DESC],
                ],
                'user.name' => [
                    'asc' => ['CONVERT(user.name using gbk)' => SORT_ASC],
                    'desc' => ['CONVERT(user.name using gbk)' => SORT_DESC],
                ],
            ],
            'defaultOrder' => ['user.create_time' => SORT_DESC],
        ],
    ]);
}
```

只有需求明确规定“一个关键词同时搜索编号、用户名、姓名、手机号”等多列时，才能增加组合 `or` 条件；必须在 SearchForm 中存在语义明确的 `keyword` 属性。只有 `_view_list.php` 确实读取嵌套关联时才预加载该关联，不能把过度查询包装成“避免 N+1”。

### 5.2 Controller

后台 Controller 统一继承 `app\controllers\Controller`，只做四件事：输入、验证、调用、响应。

统一响应入口：

- `jsonSuccess($message, $data)`
- `jsonError($message, $data, $code)`
- `jsonTable($itemView, $dataProvider, $emptyText, $viewData)`
- `renderValidate()`
- `renderModal()`

Admin AJAX 协议：

```json
{"code":1000,"message":"操作成功","data":{}}
```

| code | 含义 |
| --- | --- |
| `1000` | 成功 |
| `2000` | 可展示业务错误 |
| `5000` | 登录失效 |

新代码禁止使用 `jsonSuccess()` 历史上的“仅传 data 数组”模糊重载。JSON 响应后不得 `echo`、`die` 或输出调试内容。

Controller 必须继承或配置认证、`Authorization`、`VerbFilter`、`AccessLog`。写操作至少 POST；菜单隐藏不能代替服务端权限。覆盖 `behaviors()` 时必须合并父行为，除非入口已有完整等价保护。

禁止在 Controller 中出现：

- SQL、Query Builder 或跨表保存。
- `beginTransaction()`、`commit()`、`rollback()`。
- `Entity::STATUS_*` 比较和核心业务分支。
- 直接改实体状态、余额、库存。
- 拼 Widget DOM、JavaScript 或外部 SDK Client。

### 5.3 标准 Action 形态

```php
/**
 * 显示会员列表页面。
 */
public function actionIndex()
{
    $service = new MemberService();

    return $this->render('index', [
        'model' => new SearchForm(),
        'statusNames' => $service->getStatusNames(),
        'registerTypeNames' => $service->getRegisterTypeNames(),
    ]);
}

/**
 * 新增客户。
 */
public function actionAdd()
{
    $form = new AddForm();
    if (!$form->loadPost()) {
        return $this->render('add', ['model' => $form]);
    }
    if (!$form->validate()) {
        return $this->jsonError($form->getError());
    }

    $service = new CustomerService();
    if (!$service->add($form)) {
        return $this->jsonError($service->getError());
    }

    return $this->jsonSuccess('新增客户成功。', [
        'url' => \app\helpers\Url::to(['index']),
    ]);
}
```

Action 注释写在方法上，说明该入口的业务用途。这里以 Controller 为例，但同一规则适用于 Service、ActiveRecord、LogicModel、FormModel 及项目中的其他 PHP 类。禁止在方法内部添加以下重复注释：

```php
/** @var MemberService $service 会员后台服务 */
$service = new MemberService();
```

`new MemberService()` 已完整表达局部变量类型和用途。只有框架动态返回值导致 IDE/静态分析无法推断，并且无法通过方法返回类型修正时，才允许使用局部 `@var`；应优先补齐源方法返回类型。

列表 Action 的行视图数据通过 `jsonTable()` 第四个参数传递：

```php
return $this->jsonTable('_view_list', $dataProvider, null, [
    'statusNames' => $statusNames,
    'dataTableId' => 'customer-list',
]);
```

禁止把服务端 `viewData` 塞入 `DataTableActiveForm::table()`；后者只创建浏览器端表格壳。

标准 Action 职责矩阵：

| Action | 固定流程 | 成功输出 | 禁止 |
| --- | --- | --- | --- |
| `actionIndex()` | 创建 SearchForm；由 Service 取得字典/树；渲染 `index` | HTML 页面 | 执行列表查询、重复渲染顶部操作入口 |
| `actionList()` | SearchForm `loadPost()` → `validate()` → Service `search()` | `jsonTable('_view_list', ...)` | 在 Controller 拼查询、直接渲染 `<tr>` |
| `actionCheckAdd()/CheckModify()` | 创建对应 Form；`loadPost()` 后 `renderValidate()` | Yii ActiveForm 验证 JSON | 复用错误场景的 Form、手拼验证 JSON |
| `actionAdd()` | GET 渲染 AddForm；POST 加载、验证并调用 Service | `jsonSuccess/jsonError` | 与 Modify 共用表单 DOM、直接保存 Entity |
| `actionModify()` | 先验证 ID；GET 由 Service 取详情并装载 ModifyForm；POST 验证并调用 Service | `jsonSuccess/jsonError` | 从 POST 信任主键、开放不可修改字段 |
| `actionDetail()` | 验证 ID；Service 取得只读 Entity/DTO；渲染详情 | HTML 页面 | View 内补查关联、使用编辑表单伪装详情 |
| 状态动作 | 验证 ID/Method/权限；调用 Service 的业务动词 | `jsonSuccess/jsonError` | Controller 比较状态常量或直接 `updateAll()` |
| QueryInput 查询页 | 创建专用 SearchForm/Service 查询；使用 `renderModal()` 和同一 DataTable 协议 | 页面弹窗 HTML/AJAX 行 | 猜父页面 DOM、复制另一套选择回填协议 |

`actionList()` 的 SearchForm 验证失败必须立即返回错误，不得继续调用 `search()`。写 Action 的失败分支必须立即 `return`；禁止记录错误后继续落库或再返回成功。

---

## 6. View 层固定规范

### 6.1 View 的唯一职责

View 只允许：

- 渲染 Controller 传入的 Form、Entity/DTO 和展示配置。
- 调用 `app\helpers\Html`、项目 Widget、Formatter、Asset 和 `JsExpression`。
- 组合 AdminLTE 4 / Bootstrap 5 标准结构。
- 用 SeaJS 编排少量页面专属交互。

View 的 `use` 只允许渲染工具，例如：

```php
use app\helpers\Html;
use app\widgets\ActiveForm;
use app\widgets\DataTableActiveForm;
use yii\web\JsExpression;
```

View 严格禁止：

- `use` ActiveRecord、LogicModel、Service、Repository/DAO 或业务实现类。
- `new`、`find()`、`findOne()`、静态调用或容器获取上述领域/业务对象。
- 查询数据库、调用外部接口、开启事务、修改实体或执行状态迁移。
- 读取 Request 来决定业务语义。
- 直接比较 `$data->status === Entity::STATUS_*` 或 `in_array(... STATUS_*)`。
- 在页面复制验证、地区、日期、ComboBox、QueryInput、上传、分页、Loading、弹窗实现。
- 添加无用途的 `<div class="xxx-page">`/`wrapper`，或仅为单页样式存在的 DOM。
- 写内联 `<style>`、加载另一套框架资源、裸输出数据库文本。

View 可以对 Controller 已传入的当前实体对象调用无副作用方法：

```php
<?php if ($data->isEffective()): ?>
    <?= Html::confirmA($data->statusName, $freezeUrl, '确定要冻结该修理厂吗？', [
        'title' => '冻结修理厂',
        'success' => new JsExpression(
            "function () { $('#garage-list').data('dataTable').load(); }"
        ),
    ]) ?>
<?php endif; ?>
```

实体的 getter/predicate 必须无副作用。隐藏按钮只改善交互；真正写入仍由 Controller → Service → LogicModel 再次校验。

### 6.2 View 文件职责

```text
index.php       列表页面、SearchForm、查询组件、表头和 DataTable 壳
_view_list.php  AJAX 列表的一条 <tr>
add.php         新增 Form
modify.php      修改 Form 和受控 ID
detail.php      只读详情，按主题拆 Card
query.php       QueryInput 的选择页面
```

- Partial 以下划线开头，通过 `$this->render()` 调用，不使用 `require/include`。
- 新增页和修改页必须分别在 `add.php`、`modify.php` 中维护完整表单 DOM，禁止新建或调用 `_form.php` 共享表单 DOM。
- 即使新增与修改字段当前完全一致，也不得抽取共享 `_form.php`；两页的模型、验证 URL、按钮 ID、文案和后续业务变化必须保持独立。
- 历史页面已经使用 `_form.php` 时，新开发不得继续复制；任务涉及该页面重构时，应把表单 DOM 分别迁回 `add.php` 和 `modify.php`。
- `detail.php` 不使用可编辑外观冒充详情；按主题拆 Card，保留返回按钮。
- `query.php` 使用同一 DataTable 体系，并按 QueryInput 协议回填，不直接猜父窗口 DOM。
- 每个 Partial 在 PHPDoc 声明输入变量，不为注释而引入领域类。
- ID 使用 `{entity}-{action}` 语义命名并在页面内唯一。

### 6.3 页面与 Card 骨架

```php
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title h6 mb-0">页面标题</h3>
    </div>
    <div class="card-body">
        <!-- 内容 -->
    </div>
</div>
```

- 同页不同业务板块使用独立 Card 和 `mb-3`，形成清晰间距。
- 列表、新增、修改等同级页面入口统一由页面顶部 `TabMenu` 或既有菜单配置提供；业务 Card 内不得再次渲染同一路由入口。
- Card Header 默认只放标题，使用普通 `.card-header`。禁止为了重复放置“新增”“返回列表”等 TabMenu 已有入口而增加 `d-flex align-items-center justify-content-between`。
- 只有不属于顶部 TabMenu、且用户明确要求在当前页面提供的专属操作，才允许放进 Card Header；添加前必须检查顶部菜单中不存在相同路由。
- 页面弹窗外壳由 `renderModal()`/`layouts/modal.php` 提供，业务 View 不复制 Modal DOM。
- 栅格只使用 `.row`、`.col-*`、`.g-*`、`.gap-*`、`.offset-*`。
- 全局表单栅格由 `ActiveForm` 统一，业务页不能私设另一套列宽。

### 6.4 新增、修改、详情表单

```php
<?php $form = ActiveForm::begin([
    'layout' => 'horizontal',
    'validationUrl' => ['customer/check-add'],
]); ?>
<div class="card-body">
    <?= $form->field($model, 'name', [
        'enableAjaxValidation' => true,
    ])->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'status')->selectDropDownList($statusNames) ?>
</div>
<div class="card-footer bg-transparent">
    <div class="row g-2">
        <div class="offset-sm-3 col-sm-8 d-flex flex-wrap gap-2">
            <?= Html::button('保存', [
                'class' => 'btn btn-primary',
                'id' => 'customer-add-submit',
                'type' => 'button',
            ]) ?>
            <?= Html::backButton() ?>
        </div>
    </div>
</div>
<?php ActiveForm::end(); ?>
```

必须：

- 普通编辑表单使用 `app\widgets\ActiveForm`、`layout => horizontal`。
- label、控件和 `attributeHints` 在同一横向行，列宽来自公共 ActiveForm。
- 必填星号来自 `rules()`；服务端和 Yii 客户端验证同时生效。
- ActiveForm 未初始化时，`admin.form` 仍用 `checkValidity()` 阻止非法提交。
- 提交按钮用 `type="button"` 并交给 `admin.form.btnAjaxSubmit()`，防止双提交。
- 参与提交的不可编辑字段用 `readonly()`/`readonlyTextarea()`，灰色背景和不可编辑光标必须明显。
- 纯展示字段使用详情/只读组件，不伪造成可编辑输入框。
- 返回、确定、取消使用 `Html::backButton()/okButton()/cancelButton()`。

### 6.5 列表页面

列表固定由 `DataTableActiveForm + DataTableView + DataTableData + _view_list.php` 组成：

```php
<?php

/**
 * 后台客户列表页面。
 *
 * @var app\models\customer\SearchForm $model 客户搜索表单
 * @var array $statusNames 客户状态字典
 */

use app\widgets\DataTableActiveForm;

?>
<div class="card shadow-sm mb-3">
    <div class="card-header">
        <h3 class="card-title h6 mb-0">客户列表</h3>
    </div>
    <div class="card-body">
        <?php $form = DataTableActiveForm::begin([
            'dataTableId' => 'customer-list',
        ]); ?>
            <?= $form->field($model, 'keyword')->textInput(['size' => 12]) ?>
            <?= $form->field($model, 'status')->searchDropDownList($statusNames) ?>
            <?= $form->field($model, 'createTime')->dateRangePicker() ?>
            <?= $form->queryButton() ?>
        <?php DataTableActiveForm::end(); ?>

        <?= $form->table([
            'head' => [
                '名称' => ['width' => '30%'],
                '状态' => ['width' => '15%'],
                '添加时间' => ['width' => '20%'],
                '操作' => ['width' => '20%'],
            ],
            'url' => ['list'],
            'load' => true,
        ]) ?>
    </div>
</div>
```

- 标准列表 Card Header 只显示列表标题。顶部菜单已经包含“新增客户”时，禁止在 Header 再输出 `Html::navigate('新增客户', ...)`。
- 不要默认给 Header 添加操作按钮；是否需要页面专属操作必须来自明确需求，而不是 AI 自行补充。
- 查询控件同高同基线；文本宽度用组件参数，下拉宽度按内容自适应，禁止全局写死。
- `dataTableId`、DOM ID、JS 获取实例的 ID 完全一致且唯一。
- 条件变化/主动查询调用 `query()` 重置页码；保持条件刷新调用 `load()`。
- Loading 必须在表格内容容器内。
- 空结果、错误、每页数量、总数、页码和分页由公共组件输出；页数与分页链接同一行。
- AJAX 内容完成后调用统一 Bootstrap 动态初始化。

### 6.6 `_view_list.php` 强制契约

`_view_list.php` 是重复行模板，不是完整页面。

```php
<tr>
    <td><?= Html::encode($data->number) ?></td>
    <td><?= Html::encode($data->name) ?></td>
    <td><?= Html::encode($data->statusName) ?></td>
    <td><?= Html::encode(Yii::$app->formatter->asDatetime($data->create_time)) ?></td>
    <td><?= Html::navigate('修改', ['modify', 'id' => $data->id]) ?></td>
</tr>
```

必须同时满足：

1. 一个数据项只输出一个 `<tr>`，禁止 Card、form、完整 table、分页和 Loading。
2. `<td>` 数量、顺序与 `table()['head']` 一一对应。
3. 动态纯文本全部编码；日期、金额、百分比、掩码通过 Formatter/Helper 展示。
4. 关系在 Service 中预加载；行内禁止 `find()`、Service、外部接口和 N+1。
5. 状态文案和入口可见性调用 `$data->statusName/getStatusName()/isXxx()/canXxx()`。
6. 禁止直接比较 AR 常量、复制状态组合和写魔法数字。
7. 操作使用 `Html::navigate()/modalA()/confirmA()/exporterA()`，禁止手写 AJAX/弹窗协议。
8. 写操作使用 POST、CSRF 和后端授权；成功回调只刷新目标 DataTable。
9. 禁止 `<script>`、`<style>`、AssetBundle、全局变量和固定重复 ID。
10. Widget 在重复行内使用时 ID 必须唯一。
11. 空数据、错误、分页和总数属于 DataTable，不属于行模板。
12. 展示省略不改变真实路由 ID 和提交值。

---

## 7. Widget、Helper 与组件选择

### 7.1 唯一选择顺序

生成页面前按顺序查找：

1. `app\widgets\ActiveField` 字段方法。
2. `app\widgets` 后台应用组件。
3. `common\widgets` 跨应用组件。
4. `mylibs\yii\widgets` / `mylibs\yii\ui\adminLte\widgets` 底层组件。
5. 现有能力确实无法表达时，新增最靠近根因的公共组件。

业务 View 只传数据和配置；Widget 负责 DOM、AssetBundle、唯一 ID、事件、重复初始化、销毁/替换、空值、回显、禁用、只读和验证状态。

禁止业务 View 直接初始化组件底层 jQuery 插件，或复制组件 HTML/CSS/JS/AJAX 协议。

### 7.2 ActiveField 场景矩阵

| 场景 | 查询页 | 新增/修改页 |
| --- | --- | --- |
| 少量枚举 | `searchDropDownList()`（空项“全部”） | `selectDropDownList()`（空项“请选择”） |
| 大量、单列可识别选项 | `combobox()` | `combobox()` |
| 需查看多列再选实体 | `queryInput()` | `queryInput()` |
| 省市区 | `searchRegionDropDownList()` | `selectRegionDropDownList()` |
| 单日 | `datePicker()` | `datePicker()` |
| 日期范围 | `dateRangePicker()` | 两个 `datePicker()` 或范围组件，按业务语义 |
| 文件/图片 | 展示/下载 | `resourceFile()` |
| 不允许修改 | 普通展示 | `readonly()` / `readonlyTextarea()` |
| 富文本 | 通常只读展示 | `editor()` |
| 经纬度 | 查询条件按业务 | `setMap()` |

选择规则：少量枚举用普通 Select；大量选项且单列文本足够识别用 ComboBox；需要编号、地区、状态等多列辅助选择用 QueryInput。

关键组件契约：

- `selectRegionDropDownList()` 必须支持省市县回显、清空和三级联动，并要求提交到区县。
- `searchRegionDropDownList()` 允许停留在省、市或区县层级。
- `dateRangePicker()` 隐藏值格式为 `开始日期/结束日期`；组件负责空值、首次/AJAX/Modal 初始化。
- `combobox()` 由公共封装管理 Selectize 兼容，输入框上下原生箭头不得暴露。
- `queryInput()` 使用页面弹窗和标准回填协议，不可替换为手写隐藏 input + 按钮。
- `resourceFile()` 必须传 Resource；类型、大小、URL 和权限由 Resource/Widget 统一控制。

标准调用形态如下。参数必须以当前方法签名为准；扩展前先读实现，不能根据示例发明参数：

```php
<?= $form->field($model, 'status')->searchDropDownList($statusNames) ?>
<?= $form->field($model, 'type')->selectDropDownList($typeNames) ?>
<?= $form->field($model, 'districtId')->searchRegionDropDownList() ?>
<?= $form->field($model, 'districtId')->selectRegionDropDownList() ?>
<?= $form->field($model, 'orderDate')->datePicker() ?>
<?= $form->field($model, 'createTime')->dateRangePicker() ?>
<?= $form->field($model, 'supplierId')->combobox(
    $supplierNames,
    ['placeholder' => '请选择供应商']
) ?>
<?= $form->field($model, 'garageId')->queryInput([
    'url' => ['garage/garage/query'],
    'text' => $model->garageName,
    'title' => '查询修理厂',
]) ?>
<?= $form->field($model, 'attachment')->resourceFile($resourceOptions) ?>
<?= $form->field($model, 'content')->editor($editorOptions) ?>
<?= $form->field($model, 'coordinate')->setMap($mapOptions) ?>
<?= $form->field($model, 'number')->readonly() ?>
```

同一个模型字段只能由一个可编辑组件拥有，禁止多个控件争用相同的 `name/id`。上传、地图、编辑器和 QueryInput 所需 URL、已有值、权限和资源配置由 Controller/Service 准备，View 不自行查询。

### 7.3 非字段组件

| 组件 | 用途 | 规则 |
| --- | --- | --- |
| `DataTableActiveForm/View/Data` | AJAX 查询、表格、Loading、分页 | 列表标准入口 |
| `Table` | 同步一次性表格 | 非 AJAX 场景 |
| `LinkPager` | DataTable 分页 | 由组件生成，不直接替换 |
| `MultiSelect2Side` | 权限/角色左右穿梭 | 数据含 `id/name/selected` |
| `EditTreeView` | 可编辑树 | 回调用 `JsExpression`，后端仍授权 |
| `CheckBoxTreeView` | 树形多选 | 选中/禁用由后端提供 |
| `StatusGroup` | 状态分组筛选 | 值和名称来自领域层 |
| `TabMenu` | 页内同级导航 | Bootstrap 5 nav-tabs |
| `SidebarMenu` | AdminLTE 4 侧栏 | 只在布局使用 |
| `Wizard` | 多步骤视觉状态 | 真正流程由 Scenario/领域校验 |
| `Breadcrumbs` | 面包屑 | Bootstrap 5 `.breadcrumb-item` |
| `NavbarDropdown` | 顶栏下拉 | Bootstrap 5 dropdown |
| `ResourceFileUploadWidget` | Resource 上传 | 通常由 ActiveField 间接调用 |
| `RegionWidget` | 地区值与三级联动 | 由 ActiveField 间接调用 |
| `SetMap/ViewMap` | 地图编辑/展示 | 密钥来自配置 |
| `CkEditor` | 富文本 | 上传与输出必须安全 |
| `Code39Widget` | 条码 | 优先 `Html::code39()` |
| `AlertInfo` | 页面内非阻断提示 | 不替代提示弹框 |

### 7.4 `app\helpers\Html`

| Helper | 用途 |
| --- | --- |
| `navigate()/navigateA()/navigateButton()` | 主 iframe/当前页导航 |
| `confirmA()` | 删除、冻结、作废等确认 |
| `modalA()/modalButton()` | 页面弹窗 |
| `exporterA()` | 带当前条件导出 |
| `backButton()/cancelButton()/okButton()` | 标准表单按钮 |
| `readonly()/readonlyTextarea()` | 非 ActiveForm 只读控件 |
| `resourceImg()` | Resource 图片展示 |
| `code39()` | 条码 |
| `contrastDigital()` | 数值变化展示 |

禁止在 View 手写 `onclick`、`data-*` 协议、回填脚本和资源域名。`activeQueryInput()`、地区 AJAX option 等内部方法只供公共组件扩展。

页面弹窗调用示例：

```php
<?= Html::modalButton('选择加盟商', ['garage/query'], [
    'class' => 'btn btn-primary',
    'modalWidth' => 960,
    // 不传 modalHeight 时由页面弹窗组件按最终内容自适应。
]) ?>
```

`app\helpers\Url::toJs()` 用于生成 JavaScript 可安全消费的 URL，`Url::toResource()` 用于资源地址，项目 Json Helper 用于安全编码配置。禁止用字符串拼接替代 Helper。

---

## 8. AdminLTE 4、Bootstrap 5、CSS 与 JavaScript

### 8.1 DOM 标准

AdminLTE 4 布局只使用：

```text
.app-wrapper  .app-header  .app-sidebar
.app-main     .app-content-header  .app-content
.card         .card-header .card-body .card-footer
```

Bootstrap 5 行为只使用 `data-bs-toggle`、`data-bs-dismiss`、`data-bs-target`；关闭按钮 `.btn-close`；表单 `.form-control/.form-select/.form-check-*`；方向工具 `float-start/float-end/ms-auto/me-auto`。

禁止旧 `.wrapper/.main-header/.main-sidebar/.content-wrapper/.box/.skin-blue/.sidebar-toggle` 和 `data-toggle/data-dismiss/data-target/pull-left/pull-right`。

DOM 必须来自 AdminLTE 4、Bootstrap 5、Yii Bootstrap 5 或项目 Widget。没有语义的自定义结构不得增加；全局问题不能通过改单个 `detail.php` 解决。

### 8.2 布局

| 布局 | 用途 |
| --- | --- |
| `layouts/index.php` | 顶部、侧栏、权限菜单、主 iframe |
| `layouts/main.php` | 普通内容页 |
| `layouts/modal.php` | 页面弹窗内容页 |
| `layouts/login.php` | 登录页 |

主框架导航使用 `admin.ui.navigate()` 或 `Html::navigate()`。内容页不得依赖父页普通 DOM；父页弹窗、提示和导航走 `admin.ui`。

菜单路由必须与服务端权限路由一致。侧栏品牌区下面直接放固定搜索区；菜单列表独立滚动，搜索栏不随菜单滚动。一级/二级选中态由公共主题统一，不在业务 View 写样式。

### 8.3 CSS 归属

| 文件 | 唯一职责 |
| --- | --- |
| `vendor/mylibs/yii/ui/adminLte/assets/theme/classic.css` | 跨应用 AdminLTE 4 主题、顶栏、侧栏、菜单、状态色 |
| `admin/web/css/site.css` | 后台通用布局、登录、表单、查询、表格、分页、弹窗 |
| `admin/web/css/product.css` | 产品查询领域 |
| `admin/web/css/productSearch.css` | 独立产品/VIN/车型搜索页 |
| Widget Asset CSS | 日期、ComboBox、树、上传等组件 |

规则：

- 公共问题写到最接近根因的主题、Widget Asset 或 `site.css`。
- 页面专属 CSS 必须有真实页面语义且严格作用域；可复用后提升公共层并删除重复规则。
- 删除只匹配 AdminLTE 2 旧 DOM 的无效选择器。
- 禁止无作用域滥用 `!important`、固定所有下拉宽度、复制 Widget CSS。
- 修改公共 CSS/JS 后递增 `AppAsset::$assetVersion` 并回归全部调用方。

### 8.4 公共 JavaScript

后台公共 JavaScript 使用 SeaJS。公开入口：

| 模块 | 职责 |
| --- | --- |
| `admin.ui` | 页面弹窗、提示、Loading、导航、权限、iframe、菜单、动态初始化 |
| `admin.ajax` | `get()/post()` 和统一 `AjaxResult` |
| `admin.form` | Yii 验证、原生约束兜底、AJAX 提交、按钮禁用、Loading |
| `admin.data-table` | DataTable 的 `load()/query()/setRequestParam()` |
| `admin.dialog` | 页面弹窗与消息框底层，业务页面通常不直接调用 |
| `admin.html` | PHP Html/ActiveField DOM 的配套交互 |
| `admin.sysmenu` | AdminLTE 4 侧栏、三级菜单定位与收起 |
| `admin.message` | 消息、未读数、浏览器通知、Workerman WebSocket |
| `admin.formater` | 历史命名的金额展示模块 |
| `wgt.chart` | line/bar/pie 图表 |
| `wgt.region` | RegionWidget 联动 |
| `wgt.upload` | Upload Widget |
| `bootstrap-components.js` | 动态 tooltip/popover 初始化 |

`admin.ui` 的公开能力按职责使用：

| 职责 | 方法 |
| --- | --- |
| 页面弹窗 | `showModal()`、`closeModal()`、`getModal()` |
| 提示弹框 | `confirmBox()`、`messageBox()`、`successBox()`、`errorBox()` |
| 非阻断提醒 | `alertSuccess()`、`alertError()`、`alertWarning()`、`alertClose()` |
| Loading | `showLoading()`、`closeLoading()`；局部容器用 jQuery Loading 插件 |
| 页面/iframe | `redirect()`、`refresh()`、`navigate()`、`topWindow()`、`contentWindow()`、`contentFrame()` |
| 权限/菜单 | `verifyPermissions()`、`initAuthorization()`、`initMenu()`、`locate()`、`collapseMenu()` |
| 动态组件 | `initLayout()`、`initBootstrapComponents()` |

业务脚本标准：

```javascript
(function ($) {
    'use strict';

    seajs.use(['admin.form', 'admin.ui'], function (form, ui) {
        $('#customer-add-submit')
            .off('click.customerAdd')
            .on('click.customerAdd', function () {
                form.btnAjaxSubmit($(this), function (result) {
                    if (!result.success()) {
                        ui.errorBox(result.message());
                        return;
                    }
                    ui.successBox(result.message(), function () {
                        ui.navigate(result.data().url);
                    });
                });
            });
    });
})(window.jQuery);
```

必须：

- URL、CSRF、权限、WebSocket 地址和 Token 由 PHP/Widget 配置传入。
- 表单提交使用 `admin.form`；请求使用 `admin.ajax`；提示/导航使用 `admin.ui`。
- AJAX/Modal 动态 DOM 初始化可重复执行且不重复绑定。
- 事件使用委托或命名空间；重新初始化只解绑自身事件，禁止无范围全局 `off()`。
- 动态文本优先 `.text()`；拼 HTML 前必须编码。
- 两个页面复用或超过轻量编排的逻辑移入公共/领域 JS 模块。
- 不使用 `setTimeout` 猜组件加载完成时机。

#### 8.4.1 复杂业务页面的拆分标准

新项目只依赖脚手架保留的公共 Widget、Helper 和 `admin.ui/admin.ajax/admin.form` 等公共模块，不依赖任何旧业务 View 或旧业务 JavaScript。复杂业务页面必须根据新需求重新生成领域模块；历史页面只能帮助提炼设计原则，不能成为文件、类名、DOM 或接口依赖。

复杂页面首先按用户看到的布局和独立业务职责划分板块，再为每个板块创建一个同语义 JavaScript 方法：

```text
页面布局                         JavaScript 方法
├── 商品明细表格板块          → ItemTable()
├── 异常数据板块              → ExceptionItemTable()
├── 当前操作/汇总板块         → OperationSummaryPanel()
├── 页面操作按钮板块          → OperationActionBar()
└── 可选外部输入板块          → ExternalInputPanel()

OperationPage() 只负责 new 上述板块、连接回调和协调跨板块用例
```

必须：

- 一个页面板块对应一个构造方法；构造方法名必须反映板块的业务含义和类型，如 `XxxTable/XxxPanel/XxxForm/XxxToolbar/XxxActionBar`。
- 禁止使用 `Block1/LeftPanel/RightArea/DataRegion/Handler/Manager` 等只能描述位置或无法识别业务职责的名称。
- 板块自己的 DOM 查询、事件绑定、临时状态、校验、更新和反馈全部写在该构造方法内。
- 板块存在默认状态设置、事件绑定、组件初始化或首次渲染等初始化工作时，必须在板块内部定义私有 `init()`，并在构造方法末尾统一调用 `init()`；维护人员只需查看 `init()` 即可掌握该板块的启动流程。没有初始化工作的板块不强行保留空 `init()`。
- 每个构造方法通过参数取得自己的 `$root/options/ui/ajax`，所有选择器默认从 `$root` 开始，不能自行扫描整个页面。
- 私有变量、私有常量和内部方法保存在构造方法闭包中，不挂到 `window`。
- 构造方法末尾只返回其他板块实际需要的公开方法、只读值或常量，例如 `validate()/getTotals()/setOnChange()/reset()/destroy()`。
- 变化中的变量优先通过 `getXxx()/setXxx()` 暴露，禁止把可变数组或对象直接返回给其他板块任意修改。
- 仅当前板块使用的常量不返回；确需跨板块共用的常量集中放在返回对象的 `constants` 中，并使用 `Object.freeze()` 防止修改。
- 其他板块不得直接访问本板块内部 DOM 和私有变量；必须先取得唯一实例，再调用其公开 API。
- 页面协调方法负责 `new` 各板块、连接回调、完成整页校验和最终提交；板块对象不自行跳转页面。
- 标准表单、查询、分页、弹窗和组件初始化继续交给已有 ActiveForm/DataTable/Widget/`admin.*`；板块方法只能编排业务交互，禁止重新实现公共组件。
- 页面多次初始化、Modal 重开或 AJAX 局部替换时，先销毁旧实例，再创建新实例。

`new ItemTable()` 等实例在页面初始化时只创建一次，后续反复调用同一实例的方法；禁止为了调用一个方法而重复 `new`，否则会重复绑定事件并丢失原状态。

禁止把板块构造方法暴露到 `window`。只有十几行以内、单按钮、无状态、不会复用的页面绑定可以保留在 View；满足以下任一条件就必须移到应用 `web/js/<domain>.<feature>.js` CMD 模块：

- 脚本包含两个及以上协作对象。
- 包含多个数据区域、动态排序、派生统计、缓存恢复或外部输入状态。
- 包含动态行创建或多个 AJAX 用例。
- 超过轻量事件装配，或者另一个页面也会复用。

Widget 自身交互进入 Widget Asset；跨应用通用能力进入 `common`/`mylibs` 对应组件。新项目自己的复杂页面模块进入该应用的 `web/js`，不反向放进公共组件。业务状态机仍在服务端 LogicModel，前端只保存完成当前交互所需的临时状态。

#### 8.4.2 复杂页面布局与 DOM

需要“主要工作区 + 辅助摘要区”的页面使用 AdminLTE 4 Card 和 Bootstrap 5 Grid；具体列数和内容按新业务决定：

```html
<div id="operation-workbench" class="row g-3">
    <div class="col-12 col-xl-9">
        <div class="card card-primary card-outline shadow-sm" data-block="item-table">
            <div class="card-body"><!-- ItemTable 管理的主要操作区域 --></div>
            <div class="card-footer bg-transparent" data-block="action-bar">
                <!-- OperationActionBar 管理的页面动作 -->
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-3">
        <div class="card card-primary card-outline shadow-sm sticky-xl-top" data-block="summary">
            <div class="card-header">
                <h3 class="card-title h6 mb-0">操作摘要</h3>
            </div>
            <div class="card-body"><!-- OperationSummaryPanel 管理的内容 --></div>
        </div>
    </div>
</div>
```

- 优先给已有语义容器增加唯一 ID 作为 JS 根节点，不为脚本再套无意义 `*-page` DOM。
- 在现有 Card、table、form、toolbar 上使用 `data-block="业务板块名"` 建立 DOM 与构造方法的映射；`data-block` 只作稳定 JS Hook，不替代 Bootstrap/AdminLTE class，也不要求新增包装层。
- 右侧面板使用响应式列和 `sticky-*-top`；禁止 `position:fixed;width:23%` 等脱离栅格的内联布局。
- `select` 使用 `.form-select`，文本输入使用 `.form-control`，复选框使用 `.form-check-input`。
- 同行控件使用 `.row/.col-*`、`.d-flex/.align-items-center/.gap-*` 或 `.input-group`，禁止 Bootstrap 3/4 的 `.form-inline`。
- 显隐使用 `.d-none`；警告、错误、当前行优先使用 `.table-warning/.table-danger/.table-active` 等 Bootstrap 5 表格状态类，禁止 `.hidden/.bg-orange/.bg-fuchsia/.bg-red`。
- 宽度、固定/粘性位置和状态视觉进入有根节点作用域的 CSS 或组件 Asset，禁止 View 内联 `style`。
- 行业务 ID 放入 `data-item-id` 或带语义前缀的 DOM ID，例如 `operation-item-123`；禁止直接用纯数字作为选择器 ID。
- 增减数量、移除等动作使用 `<button type="button" class="btn ...">`，不能使用 `href="javascript:void(0)"`。
- 业务确实需要声音、图片等反馈资源时，URL 必须由 Resource/Url Helper 生成，并准备资源不可用时的文字或视觉反馈；普通页面不得默认引入媒体资源。

#### 8.4.3 CMD 模块与页面入口

领域模块沿用 SeaJS/CMD，并把配置作为数据注入：

```javascript
define(function (require, exports) {
    'use strict';

    var ui = require('admin.ui');
    var ajax = require('admin.ajax');
    var dataKey = 'operationPage';

    /**
     * 创建明细表格板块。
     *
     * @param {jQuery} $root 明细表格根节点
     * @param {Object} options 明细板块配置
     * @return {Object} 明细表格公开接口
     */
    function ItemTable($root, options) {
        var eventNamespace = '.itemTable';
        var STATE_READY = 'ready';
        var emptyValue;
        var onChange = null;

        options = options || {};
        emptyValue = options.emptyValue === undefined ? '' : options.emptyValue;

        function getValues() {
            var values = [];

            $root.find('[data-item-id]').each(function () {
                var $item = $(this);
                values.push({
                    id: $item.data('itemId'),
                    value: $item.find('[data-field="value"]').val()
                });
            });

            return values;
        }

        function getSummary() {
            return {
                count: $root.find('[data-item-id]').length
            };
        }

        function validate() {
            return $root.find(':invalid').length === 0;
        }

        function getState() {
            return STATE_READY;
        }

        function setOnChange(callback) {
            onChange = typeof callback === 'function' ? callback : null;
        }

        function notifyChange() {
            if (onChange) {
                onChange(getSummary());
            }
        }

        function reset() {
            $root.find('[data-field="value"]').val(emptyValue);
            notifyChange();
        }

        function bindEvents() {
            $root.off(eventNamespace);
            $root.on(
                'change' + eventNamespace,
                '[data-field="value"]',
                notifyChange
            );
        }

        function destroy() {
            $root.off(eventNamespace);
            onChange = null;
        }

        function init() {
            bindEvents();
            notifyChange();
        }

        init();

        return {
            constants: Object.freeze({
                STATE_READY: STATE_READY
            }),
            getValues: getValues,
            getSummary: getSummary,
            getState: getState,
            validate: validate,
            setOnChange: setOnChange,
            reset: reset,
            destroy: destroy
        };
    }

    /**
     * 创建操作摘要板块。
     *
     * @param {jQuery} $root 摘要板块根节点
     * @return {Object} 摘要板块公开接口
     */
    function OperationSummaryPanel($root) {
        var isDestroyed = false;

        function update(summary) {
            if (isDestroyed) {
                return;
            }
            $root.find('[data-summary="count"]').text(summary.count);
        }

        function destroy() {
            isDestroyed = true;
        }

        return {
            update: update,
            destroy: destroy
        };
    }

    /**
     * 创建页面操作栏板块。
     *
     * @param {jQuery} $root 操作栏根节点
     * @return {Object} 操作栏公开接口
     */
    function OperationActionBar($root) {
        var eventNamespace = '.operationActionBar';
        var onSubmit = null;
        var onReset = null;

        function setOnSubmit(callback) {
            onSubmit = typeof callback === 'function' ? callback : null;
        }

        function setOnReset(callback) {
            onReset = typeof callback === 'function' ? callback : null;
        }

        function setPending(pending) {
            $root.find('button').prop('disabled', pending);
        }

        function bindEvents() {
            $root.off(eventNamespace);
            $root.on('click' + eventNamespace, '[data-action="submit"]', function () {
                if (onSubmit) {
                    onSubmit();
                }
            });
            $root.on('click' + eventNamespace, '[data-action="reset"]', function () {
                if (onReset) {
                    onReset();
                }
            });
        }

        function destroy() {
            $root.off(eventNamespace);
            onSubmit = null;
            onReset = null;
        }

        function init() {
            bindEvents();
            setPending(false);
        }

        init();

        return {
            setOnSubmit: setOnSubmit,
            setOnReset: setOnReset,
            setPending: setPending,
            destroy: destroy
        };
    }

    /**
     * 创建页面协调器并连接各业务板块。
     *
     * @param {jQuery} $root 页面根节点
     * @param {Object} options 服务端生成的页面配置
     * @return {Object} 页面公开接口
     */
    function OperationPage($root, options) {
        var itemTable = new ItemTable(
            $root.find('[data-block="item-table"]'),
            options.itemTable || {}
        );
        var summaryPanel = new OperationSummaryPanel(
            $root.find('[data-block="summary"]')
        );
        var actionBar = new OperationActionBar(
            $root.find('[data-block="action-bar"]')
        );

        itemTable.setOnChange(function (summary) {
            summaryPanel.update(summary);
        });
        summaryPanel.update(itemTable.getSummary());

        actionBar.setOnReset(function () {
            ui.confirmBox('确定要重置吗？', function () {
                itemTable.reset();
            });
        });

        actionBar.setOnSubmit(function () {
            if (
                itemTable.getState() !== itemTable.constants.STATE_READY
                || !itemTable.validate()
            ) {
                ui.messageBox('请检查页面输入。');
                return;
            }

            actionBar.setPending(true);
            ajax.post(options.submitUrl, {
                items: itemTable.getValues()
            }, function (result) {
                actionBar.setPending(false);
                if (!result.success()) {
                    ui.errorBox(result.message());
                    return;
                }
                ui.successBox(result.message());
            }, function () {
                actionBar.setPending(false);
                ui.errorBox('提交请求失败。');
            });
        });

        function destroy() {
            actionBar.destroy();
            summaryPanel.destroy();
            itemTable.destroy();
            $root.removeData(dataKey);
        }

        return {
            validate: itemTable.validate,
            getValues: itemTable.getValues,
            destroy: destroy
        };
    }

    exports.init = function (selector, options) {
        var $root = $(selector);
        var current;
        var page;

        if (!$root.length) {
            return null;
        }

        current = $root.data(dataKey);
        if (current && typeof current.destroy === 'function') {
            current.destroy();
        }

        page = new OperationPage($root, options || {});
        $root.data(dataKey, page);
        return page;
    };
});
```

View 只负责安全生成配置和启动模块：

```php
<?php
$pageOptions = [
    'submitUrl' => Url::to(['domain/entity/submit']),
    'itemTable' => [
        'emptyValue' => '',
    ],
];
?>
<script>
seajs.use(['domain.operation-page'], function (page) {
    page.init('#operation-workbench', <?= Json::htmlEncode($pageOptions) ?>);
});
</script>
```

- 普通 AJAX URL 使用 `Url::to()` 后放入 `Json::htmlEncode()` 配置；需要先做前端权限路由验证时使用 `Url::toJs()` 的 `[route, url]` 契约。
- 数组、缓存和文本用 `Json::htmlEncode()` 注入，禁止 `urlencode(json_encode()) + decodeURIComponent() + eval()`、`new Function()` 或字符串拼 JavaScript。
- View 中不得重复声明模块已经拥有的函数；一个页面只保留一次 `seajs.use()` 装配入口。
- CMD 模块只 `require()` 实际使用的依赖，依赖名和回调参数顺序必须一致。
- 模块文件加入现有 Asset/SeaJS 版本机制；修改后按 8.3 递增 `assetVersion`。
- `domain.operation-page` 只是命名格式示例，必须替换成新项目真实业务域；脚手架不需要预先保留任何同名业务模块。

上例的协作边界必须原样理解：`OperationPage()` 只创建一次 `itemTable/summaryPanel/actionBar`；明细变化通过 `setOnChange()` 通知摘要板块，提交通过 `getValues()/validate()` 读取明细板块公开能力，状态常量通过只读的 `itemTable.constants.STATE_READY` 访问。协调器和其他板块均不得使用 `itemTable.$root`、重新查询明细区 DOM，或直接修改明细区闭包变量。构造方法允许在 `new Xxx()` 时显式返回最小公共 API 对象，这是本项目用闭包隔离板块内部实现的约定。

#### 8.4.4 状态、DOM 同步与数据契约

- 服务端输出是初始事实，页面模块建立明确状态；不要把散落的 DOM 文本当作唯一数据仓库。
- 字段值、选中项、排序和其他交互变化先经过单一更新方法，再统一同步区域状态、提交值和派生摘要。
- 查找当前行使用 `$element.closest('tr')` 和 `[data-*]`，禁止连续 `.parent().parent()`、`childNodes[0]` 或依赖列序号读取输入框。
- 同一状态只由一个方法写入；`updateItem()`、`removeItem()`、`syncSubmitValue()`、`updateSummary()` 等步骤名称必须表达副作用。
- 提交结构优先使用命名数组输入，例如 `items[123][value]`、`items[123][remark]`；兼容旧接口的复合字符串必须由一个专用序列化器处理，服务端仍逐字段验证。
- 前端 `validate()` 只用于即时体验；数值边界、记录归属、状态、权限和重复提交必须由 Form/LogicModel 再验证。
- 数字统一使用 `Number.parseInt(value, 10)` 并检查 `Number.isNaN()`；相等判断使用 `===/!==`。
- 所有变量必须用 `var/let/const` 声明；当前 SeaJS 模块可沿用 `var`，但禁止 `$tr/$input/n1/tr` 等隐式全局变量。
- 构造器/工厂名使用 `PascalCase`，函数和状态使用 `camelCase`，jQuery 对象以 `$` 开头，布尔方法使用 `is/has/can` 前缀。

#### 8.4.5 事件绑定与生命周期

- 事件绑定使用 `.on('event.namespace', selector, handler)`；动态行统一使用根节点委托。
- 初始化前仅 `.off('.currentNamespace')` 清理自己的事件，禁止 `.unbind()`、无命名空间 `.off()` 或删除其他组件监听器。
- 模块必须提供 `destroy()`，清理根节点、`document/window` 监听器、计时器、网络连接、播放器和插件实例。
- 禁止同时使用 `.click(handler)`、`.change(handler)` 与 `.on()` 混合管理同一模块。
- 处理器只读取事件、调用语义方法和展示结果；长业务步骤拆为私有方法。
- 使用 `event.preventDefault()` 表达阻止默认行为，不通过 `return false` 同时隐式阻止传播。
- Bootstrap Modal/AJAX 内容销毁时必须执行模块清理，重复打开不得出现一次操作触发多次请求。

#### 8.4.6 键盘与外部输入设备（按需）

只有业务明确需要快捷键、扫码枪或其他模拟键盘设备时才创建 InputAdapter。以模拟键盘输入的设备为例，新实现必须：

- 使用 `keydown` 和 `event.key`，禁止已弃用的 `keypress/keyCode/charCode`。
- 监听器有专用命名空间，只在页面激活时绑定，并能 `start()/stop()/destroy()`。
- 焦点位于 `input/textarea/select/[contenteditable]` 时忽略扫描输入，不通过 focus/blur 删除整个 document 的键盘事件。
- 按真实设备协议配置字符缓冲、最大长度、结束键和最大间隔；超时自动清空，防止人工输入拼入下一次设备输入。
- 不屏蔽 Tab、浏览器快捷键和辅助技术按键；只消费已经识别为当前设备协议序列的按键。
- 输入内容先 `trim()` 并按业务格式归一化、验证，再调用页面对象或 AJAX 查询。
- AJAX 查询期间按业务决定串行、去重或取消旧请求，防止连续设备输入造成乱序覆盖。

#### 8.4.7 动态 DOM、安全与反馈

- 服务端返回文本使用 `.text()` 写入。需要创建行时，用 jQuery/DOM API 创建节点并分别 `.text()`、`.attr()`；禁止把 `data.categoryName` 等直接拼进 HTML 字符串。
- 动态内容插入后调用统一的 `ui.initBootstrapComponents()`；不得手工重复初始化 Tooltip/Popover。
- 提示使用 `ui.alertSuccess/alertError/alertWarning`，阻断确认使用 `confirmBox/messageBox/successBox/errorBox`，不得复制 Alert/Modal DOM。
- `admin.ajax` 回调接收 `AjaxResult`，必须先判断 `result.success()`；同时提供网络错误回调并恢复按钮、Loading 和交互状态。
- 写请求期间禁用相应按钮，必要时暂停当前 InputAdapter，完成后恢复；防止双击或连续输入产生重复写入。
- 页面级 Loading 与局部表格 Loading 按实际范围选择，不能遮罩无关区域。
- 业务使用音频反馈时，调用 `audio.play()` 必须处理 Promise 失败；浏览器禁止自动播放或资源不存在时，业务流程仍应继续并提供可见反馈。

#### 8.4.8 通用设计判定表

| 设计选择 | 规范结论 |
| --- | --- |
| 页面语义板块与同名构造方法一一对应 | 必须；名称同时表达业务含义和板块类型 |
| 页面入口为每个板块创建一个实例 | 必须；后续复用实例，不得为调用方法重复 `new` |
| 板块包含初始化工作 | 在板块内部定义私有 `init()`，由构造方法末尾统一调用 |
| 跨板块调用实例返回的方法、只读值、常量或回调 | 必须；公开面保持最小化 |
| 跨板块查询对方 DOM、读取或修改私有变量 | 禁止；由所属板块提供语义 API |
| 协调器组合多个单一职责对象 | 推荐；对象通过参数、返回值和回调协作 |
| 闭包/实例保存页面临时状态 | 推荐；不得暴露隐式全局变量 |
| CMD 模块的公开 `init()` 返回页面最小 API | 推荐；重复调用必须先销毁旧实例，并提供 `destroy()` |
| SeaJS 注入公共 `admin.*` 模块 | 必须；不得复制 Ajax、Modal、Loading、表单提交实现 |
| 主工作区与辅助区 | 按需求使用响应式 Grid + Card；不是所有页面的固定布局 |
| 大段 View 内联脚本和多个全局函数 | 禁止；生成独立业务 CMD 模块 |
| `eval()`/`new Function()` 恢复服务端状态 | 禁止；使用安全 JSON 配置 |
| 字符串拼接服务端数据生成 DOM | 禁止；使用 DOM/jQuery 节点和 `.text()` |
| 无命名空间事件、旧键盘 API、阻止 Tab | 禁止；使用生命周期明确的标准事件 |
| 旧框架 class 和内联 style | 禁止；使用 Bootstrap 5/AdminLTE 4 标准类及作用域 CSS |
| 依赖父级层数、childNodes 或固定列序号 | 禁止；使用 `closest()`、语义 class 和 `data-*` |

本节是业务无关的生成规则。新项目可以只保留公共 Widget、Helper、Asset 和 `web/js` 公共模块；任何领域页面、领域 CMD 模块、选择器、配置字段和对象名称都必须根据新项目的数据表与业务需求重新生成。

### 8.5 Workerman WebSocket

```javascript
var socket = new WebSocket(wsUrl);
```

连接地址和 Token 从 View 配置取得。公共模块处理 `open/message/error/close`、心跳、断线重连和 Token 过期。禁止重新引入 `socket.io.js`，View 不实现服务端业务协议。

### 8.6 提示弹框与页面弹窗

两类弹窗完全分离：

**提示弹框**

- 成功绿色、失败红色、确认蓝色。
- 默认约 `500 × 182`，内容短时不得出现不协调的大空白。
- 头部/底部用较深状态色，正文用稍浅同色系，不能是突兀白色。
- 右上角保留清晰关闭图标和点击区域，但不显示多余厚重 button 外观。
- 使用 `ui.successBox()/errorBox()/messageBox()/confirmBox()`。

**页面弹窗**

- 使用 `Html::modalA()/modalButton()` 或 `ui.showModal()`。
- 调用方未指定宽高：宽高按最终内容自适应，自动高度最高为父页面可视高度 50%，超出内部滚动。
- 调用方指定 `modalWidth/modalHeight`：严格使用指定值。
- 应快速展示稳定外壳/Loading，并在取得最终内容尺寸后一次稳定布局；异步表格渲染不得把宽高从正常值突变为窄框。
- 页面使用 `renderModal()` 或 `$this->layout = 'modal'`，业务 View 不复制 Modal DOM。

### 8.7 公共问题的唯一归属

| 现象 | 首先修复 |
| --- | --- |
| 省市县无数据、无回显、无三级联动 | `ActiveField` 地区入口、`RegionWidget`、`wgt.region` |
| 日期不弹出、范围无效、null Deprecated | DatePicker/DateRangePicker Widget、Asset 和统一解析 |
| ComboBox 样式、箭头、Modal 下拉异常 | `ActiveField::combobox()`、Selectize Asset/Bootstrap 5 主题 |
| QueryInput 高度、水平对齐、回填异常 | `DataTableActiveForm/ActiveField`、`admin.html` |
| 必填项可绕过 | `ActiveForm` 与 `admin.form` |
| Loading 不在表格中 | `DataTableView` / `admin.data-table` |
| 页数和分页不同行 | `DataTableData/DataTableView/LinkPager` |
| 提示框颜色、高度、关闭图标异常 | `admin.dialog/admin.ui` 与主题 CSS |
| 页面弹窗尺寸突变 | 页面 Modal 公共测量和渲染流程 |
| label、只读控件、hint、按钮整体错位 | `ActiveForm/ActiveField` 与 `site.css` |
| 顶栏、侧栏、菜单选中态异常 | AdminLTE 主题、布局和 `admin.sysmenu` |

只有确认公共组件已经正确、问题确属某页独有业务结构时，才能修改业务 View。禁止为公共问题创建页面专属 `*-page` 包装和 CSS/JS。

---

## 9. 缓存、任务、消息、资源与配置

### 9.1 缓存和锁

缓存类放 `common/caching` 并继承 `mylibs\yii\caching\AppCache`。每个类明确 Key 前缀/维度、TTL、回源类型、空值语义和所有失效入口。数据库提交成功后再失效缓存。

- `0`、`false`、空数组与未命中必须区分。
- Redis 删除使用当前 API `del()`，禁止已弃用 `delete()`。
- 登录次数/一次性 Token 使用原子操作、短 TTL、不可预测 Token。
- 新分布式锁使用 `SET key token NX PX ttl` 或成熟 Mutex，释放时比较 owner token。
- 历史 `MutexLock` 不具备安全 owner token，新并发流程禁止复制。

### 9.2 任务、消息、日志、邮件

- Job 参数可序列化且尽量只含 ID/必要快照；任务必须幂等，明确重试、退避、超时和失败日志。
- 新代码使用 `pushDelay()` 正确拼写；历史 `pushDealy()/dealy()` 不扩散。
- 关键副作用使用可靠队列/Outbox，禁止依赖 shutdown 式 `DelayEvents`。
- Message 明确接收用户、类型、标题、内容、extras；不得含秘密和不必要个人信息。
- 日志包含业务 ID、操作人、动作、结果和异常栈，但过滤所有敏感字段和大文本。
- 邮件使用 Symfony Mailer；模板动态内容编码，附件校验路径/大小/可读性。

### 9.3 Resource

Resource 负责上传地址、参数、临时文件、正式迁移和访问 URL。正式文件按业务和月份组织，文件名由服务端生成，防止目录穿越。私有合同、证书不得放公开 Web 目录，下载必须授权。临时文件有生命周期和清理任务。

Importer 与下载模板的列名、顺序、示例和编码必须一致；规则变化同步更新。模板不得含真实用户或生产数据。若用户明确删除某模板，不得擅自恢复。

### 9.4 配置

配置分层：

- `main.php`：环境一致的组件和别名。
- `main-dev.php/main-test.php/main-prod.php`：环境差异。
- `*-local.php` 或环境变量：实例秘密并加入 `.gitignore`。
- `mylibs\yii\config\Configure` 只负责定位配置文件，不是秘密管理工具。

组件实现类完全变化时，合并配置前先移除旧字段，避免 `ArrayHelper::merge()` 遗留不兼容配置。运行期业务数据不能写回 PHP 配置。

### 9.5 外部适配器

支付、微信、OAuth、AWS、JWT、2FA、Redis、邮件等必须通过 Gateway/Adapter/Service 边界，不在 Controller 创建 SDK Client，也不让 SDK 对象进入 Entity/View。

- 支付/微信：验证签名、幂等和重放，网络调用不长期占用数据库事务。
- JWT：固定算法白名单，校验 `exp/nbf/iss/aud/jti`，支持密钥轮换。
- 2FA：密钥加密保存，二维码只展示一次，启用前二次确认并提供恢复方式。
- AWS：区域、Endpoint、凭据由环境注入，上传仍经过 Resource 安全校验。
- 设备识别只用于体验/统计，不能作为身份或权限依据。

---

## 10. 新业务模块生成流程

### 10.1 用户应提供的业务事实

| 信息 | 示例 |
| --- | --- |
| 业务域/实体 | `customer` / Customer |
| 字段及类型 | 名称、地区、联系人、状态、备注 |
| 数据约束 | 同地区名称唯一，金额两位小数 |
| 状态机 | 有效 → 冻结 → 解冻 |
| 权限 | 查看、新增、修改、冻结 |
| 页面 | 列表、新增、修改、详情、查询弹窗 |
| 查询条件 | 编号、名称、地区、状态、日期 |
| 副作用 | 清缓存、Trace、站内消息 |

AI 负责从框架中自动选择 DataTable、地区、日期、ComboBox、QueryInput、上传、树、状态组、地图、富文本、条码、导航和弹窗。只有交互选择会改变业务流程、上传保密级别不明、状态机缺失等实质决策才询问用户。

### 10.2 固定生成步骤

1. 核对表、字段、默认值、金额精度、主键、唯一键、外键。
2. 创建 Entity：规则、时间 Behavior、relations、枚举、无副作用语义方法。
3. 创建 LogicModel：恢复/创建入口、动作、Error 契约、事务、Trace、并发条件。
4. 按需创建 Value Object、Sequence、Importer、Statistics、Behavior、Message/Outbox。
5. 创建 Add/Modify/Search Form。
6. 创建 Service：用例、查询、分页、预加载、错误转换。
7. 创建 Controller：输入、验证、调用、响应和权限/Verb。
8. 创建标准 View，按字段语义选择公共 Widget。
9. 只在必要时增加领域 JS；公共问题修复公共组件。
10. 编写正常、失败、回滚、并发、幂等、权限和浏览器测试。

#### 10.2.1 从数据表到代码的逐字段映射

AI 不能只根据表名批量生成属性。每个字段必须先形成一行映射，再决定进入哪些层：

| Schema/业务事实 | Entity | Form | Service/LogicModel | View |
| --- | --- | --- | --- | --- |
| 非空且由用户输入 | 与 Schema 一致的规则 | Add/Modify 按场景 `required` | 使用安全属性 | ActiveField 自动显示必填标记 |
| 非空但由系统生成 | rules 与 Behavior/默认值一致 | 不开放输入 | LogicModel 生成 | 只读或不显示 |
| 长度、精度、枚举范围 | string/number/in 规则 | 按输入边界重复格式规则 | 领域层再次检查关键约束 | 选择正确 Widget 和 Formatter |
| 外键 | relation + 基础类型 | 接收 ID 并校验格式 | 校验对象存在、归属和状态 | Select/ComboBox/QueryInput 按数据规模选择 |
| 状态 | 常量、名称、`isXxx()` | 通常不作为任意可写字段 | LogicModel 提供业务动词 | 调 getter/predicate，不比较常量 |
| create/update/status time | safe + Behavior | 不接收客户端伪造值 | 领域动作决定状态时间 | Formatter 展示；查询用日期组件 |
| 金额/数量 | 精度、范围与数据库一致 | 输入格式和跨字段校验 | 并发、余额/库存规则 | Formatter；不可用普通浮点拼接 |
| 只读业务字段 | Entity 正常映射 | 不进入 Modify 安全属性 | Service 从已存在实体取得 | readonly/detail 组件明确区分 |
| 软删除字段 | 状态语义和默认定位方法 | 不直接开放 | LogicModel 删除/恢复动作 | 根据语义方法显示入口 |

每个页面字段还必须回答：

```text
谁输入？             用户输入 / 系统生成 / 关联选择 / 只读展示
什么时候必填？       Add / Modify / Search / 特定 Scenario
是否允许修改？       永不 / 特定状态 / 始终
怎样选择？           普通 Select / ComboBox / QueryInput / Region / Date
怎样保存？           单实体字段 / 聚合参数 / 明细数组 / 不保存
怎样展示？           原文编码 / Formatter / Entity getter / Resource
怎样查询？           精确 / like / range / relation / 不可查询
怎样授权和并发？     路由权限 / 归属 / 旧状态条件 / 唯一约束
```

任一问题没有答案时，不得通过默认生成一个可编辑文本框来掩盖需求缺失。

#### 10.2.2 从业务动作到完整调用链

每个新增、修改、冻结、审核、删除、导入等动作必须先写出同一份动作契约：

```text
动作名称：freezeCustomer
入口与 Method：POST customer/customer/freeze
输入：客户 ID、可选原因
权限：customer/customer/freeze
前置条件：记录存在、属于可管理范围、当前为有效
事务写入：客户状态 + status_time + trace
并发条件：UPDATE 时携带旧状态，检查影响行数
提交后副作用：缓存失效、消息/任务
成功输出：jsonSuccess(message, data)
业务失败：LogicModel false/Error → Service error → jsonError
系统异常：向上抛给统一错误处理
页面反馈：confirmA → successBox → 刷新目标 DataTable
```

随后按契约生成代码；任何一项不能被明确映射到文件和方法，说明分层尚未完成：

| 契约内容 | 代码位置 |
| --- | --- |
| 输入字段和格式 | `XxxForm::rules()` |
| 记录存在、状态、归属、并发和事务 | `XxxModel::<businessVerb>()` |
| Form 到领域参数、领域错误转换 | `XxxService::<businessVerb>()` |
| Method、权限、load/validate、JSON | `XxxController::actionXxx()` |
| 按钮显示条件 | Entity 无副作用 predicate + View Helper |
| 二次确认、成功刷新 | `Html::confirmA()`/`admin.ui`/目标 DataTable |
| 正常、失败、重复操作 | LogicModel/Service/功能测试 |

### 10.3 标准后台模块文件集合

以 `customer` 为例，完整 CRUD 默认至少包含以下文件。数据库已有能力可以复用，但不能因为某一层代码较短就把它合并进别层：

```text
common/models/customer/Customer.php          # Entity：表映射、规则、关系、状态语义
common/models/customer/CustomerModel.php     # LogicModel：新增、修改、状态动作、事务

admin/models/customer/SearchForm.php         # 列表查询输入
admin/models/customer/AddForm.php            # 新增输入
admin/models/customer/ModifyForm.php          # 修改输入
admin/models/customer/CustomerService.php    # Admin 用例、详情和列表查询

admin/controllers/customer/CustomerController.php

admin/views/customer/customer/index.php      # 查询表单、表头、DataTable 壳
admin/views/customer/customer/_view_list.php # 一条数据行
admin/views/customer/customer/add.php         # 完整新增 DOM
admin/views/customer/customer/modify.php      # 完整修改 DOM
admin/views/customer/customer/detail.php      # 只读详情
admin/views/customer/customer/query.php       # 仅在 QueryInput 需要选择页时创建
```

文件职责必须能逐项核对：

| 文件 | 必须包含 | 禁止包含 |
| --- | --- | --- |
| Entity | `tableName/rules/labels/behaviors/relations`、枚举、getter、predicate、稳定定位方法 | Request、事务、跨表流程、HTML |
| LogicModel | `getById/create/modify`、状态动作、事务、Trace、并发条件 | Controller 响应、View、输入加载 |
| SearchForm | 查询字段及输入格式规则 | 保存、状态迁移、分页查询 |
| AddForm | 新增允许输入的字段和规则 | 修改页场景、保存实体 |
| ModifyForm | 修改允许输入的字段和规则 | 继承新增页 DOM、擅自开放只读字段 |
| Service | `search/getDetail/add/modify/<business-action>`、字典和 View 数据准备 | Request、HTML、核心状态规则 |
| Controller | `index/list/add/modify/detail/check-*` 及明确业务 Action | SQL、事务、状态常量判断、Widget DOM |
| `index.php` | DataTable 查询字段、表头、URL 和加载配置 | 重复顶部菜单按钮、数据库查询 |
| `_view_list.php` | 单个 `<tr>`、编码后的展示值和 Helper 操作入口 | 完整 table、分页、脚本、AR 常量判断 |
| `add.php` | 新增页独立完整表单、提交脚本 | 调用共享 `_form.php` |
| `modify.php` | 修改页独立完整表单、提交脚本、只读字段 | 调用共享 `_form.php` |
| `detail.php` | Card 分区、只读展示、标准返回按钮 | 可编辑假控件、页面专属布局补丁 |

只生成 `Controller + View`、只生成 `Entity + Controller`，或者把 Add/Modify 共用 `_form.php`，都不算完成标准模块。若需求明确不需要某个页面或动作，变更清单必须写明“不生成的文件、原因和替代入口”。

列表功能必须形成以下闭环：

```text
actionIndex() → SearchForm + 字典 → index.php
actionList()  → SearchForm load/validate → Service::search()
              → jsonTable('_view_list', ActiveDataProvider, ..., viewData)
index.php     → DataTableActiveForm → table(['url' => ['list']])
_view_list.php → 一个 <tr>，成功动作只刷新同一 dataTableId
```

写入功能必须形成以下闭环：

```text
add.php/modify.php → ActiveForm + admin.form
Controller         → loadPost() → validate() → Service
Service            → getSafeAttributes() → LogicModel
LogicModel         → 领域校验 → Transaction → Entity/Trace → commit
Controller         → jsonSuccess/jsonError
View JavaScript    → successBox/errorBox → navigate/refresh
```

### 10.4 生成前输出

编码前必须先给出：

```text
需求理解
数据库约束
状态机
分层文件清单
复用组件及真实调用依据
测试与浏览器验收清单
```

这不是要求等待确认；需求明确时输出后直接实现。

---

## 11. 端到端最小模板

本节只展示层之间怎样连接，字段、状态、权限和错误必须按真实业务替换。不得只替换类名后机械复制。

### 11.1 SearchForm 与 Service

```php
/**
 * 客户列表查询输入模型。
 */
final class SearchForm extends \common\models\FormModel
{
    public $id;
    public $name;
    public $status;
    public $createTime;

    /**
     * 返回查询字段验证规则。
     */
    public function rules(): array
    {
        return [
            [['id', 'status'], 'integer'],
            [['name'], 'string', 'max' => 64],
            [['createTime'], 'safe'],
        ];
    }
}
```

```php
/**
 * 客户后台应用服务。
 */
final class CustomerService extends \app\models\Service
{
    /**
     * 按已验证条件查询客户列表。
     *
     * @return \yii\data\ActiveDataProvider 客户列表数据提供器
     */
    public function search(SearchForm $form)
    {
        $query = Customer::find();
        $query->andFilterWhere(['customer.id' => $form->id]);
        $query->andFilterWhere(['like', 'customer.name', $form->name]);
        $query->andFilterWhere(['customer.status' => $form->status]);

        $query->andWhere(\mylibs\yii\db\QueryHelper::timeRange(
            'customer.create_time',
            $form->createTime
        ));

        return new \yii\data\ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => $this->getOption('pagesize')],
            'sort' => [
                'attributes' => [
                    'customer.id',
                    'customer.status',
                    'customer.create_time' => [
                        'asc' => ['customer.id' => SORT_ASC],
                        'desc' => ['customer.id' => SORT_DESC],
                    ],
                    'customer.name' => [
                        'asc' => ['CONVERT(customer.name using gbk)' => SORT_ASC],
                        'desc' => ['CONVERT(customer.name using gbk)' => SORT_DESC],
                    ],
                ],
                'defaultOrder' => ['customer.create_time' => SORT_DESC],
            ],
        ]);
    }
}
```

日期格式解析由 DateRangePicker/QueryHelper 的既有契约处理，不在每个 Service 自创解析格式。所有列表 Service 以 5.1.1 为唯一查询风格；本节只展示不同层的连接关系。

### 11.2 写入链路

```php
/**
 * 新增客户。
 */
public function add(AddForm $form): bool
{
    $result = CustomerModel::create($form->getSafeAttributes());
    if (\common\models\Error::isError($result)) {
        return $this->addErrors($result->getErrors());
    }

    return true;
}
```

```php
/**
 * 冻结指定客户。
 */
public function freeze(int $id): bool
{
    $customer = CustomerModel::getById($id);
    if ($customer === null) {
        return $this->setError('客户不存在。');
    }

    if (!$customer->freeze()) {
        return $this->addErrors($customer->getErrors());
    }

    return true;
}
```

Controller 不取得 `$customer->getData()` 后自行改状态；Service 也不能复制 `freeze()` 的领域判断。

### 11.3 页面脚本

View 中只保留绑定按钮和处理结果：

```javascript
seajs.use(['admin.form', 'admin.ui'], function (form, ui) {
    $('#customer-add-submit')
        .off('click.customerAdd')
        .on('click.customerAdd', function () {
            form.btnAjaxSubmit($(this), function (result) {
                if (!result.success()) {
                    ui.errorBox(result.message());
                    return;
                }
                ui.successBox(result.message(), function () {
                    ui.navigate(result.data().url);
                });
            });
        });
});
```

不得在页面脚本中重写验证、Loading、AjaxResult、Modal、DataTable 或 Bootstrap 初始化。

---

## 12. 完成定义与验收门禁

### 12.1 静态与自动检查

按修改范围执行：

- `composer validate`
- PHP 语法检查
- `php yii help`
- PHPStan
- Codeception 单元/功能测试
- `git diff --check`
- 检查 PHP 日志无新增 Deprecated、Warning、Notice

不能运行的检查必须说明原因和未覆盖风险，不能写“已通过”。

### 12.2 领域验收

- 表结构与 Entity rules 一致，唯一性有数据库约束。
- Add/Modify/Search Form 安全属性和场景互不污染。
- Controller 无 SQL、事务和核心状态判断。
- Service 无 Request/HTML/JSON，LogicModel 无 Admin/View 依赖。
- 写动作错误契约单一；每个业务失败分支显式回滚。
- 不可预期异常能由事务包装器回滚并进入统一错误处理。
- 状态更新检查旧状态，并发下无超扣、重复编号或重复操作。
- Trace、缓存失效、消息和提交时序正确。
- 正常、非法状态、记录不存在、回滚、幂等、并发和权限测试齐全。

### 12.3 View 与浏览器验收

- View 没有引入/查询 Entity、LogicModel、Service、Repository。
- View 条件分支调用实体语义方法，不直接比较状态常量。
- DOM 仅为 AdminLTE 4 / Bootstrap 5 / 项目 Widget 输出。
- Card 分区、横向表单栅格、label/控件/hint、只读背景和按钮一致。
- 查询输入同高同基线，下拉字体与输入框一致，宽度符合自适应规则。
- 地区显示、回显、清空和三级联动正常。
- 日期单选/范围组件可由鼠标触发、可回显且真实过滤生效。
- ComboBox 无旧样式、无上下箭头，普通页和 Modal 均正常。
- QueryInput 查询、选择和回填正常。
- 必填项在客户端阻止提交，服务端仍验证。
- 列表空数据、Loading、总数、页码、分页和重复查询正常。
- Loading 位于表格容器，分页信息同一行。
- 提示弹框颜色、高度、正文色和关闭图标符合状态规范。
- 页面弹窗自动/指定尺寸正确，异步渲染不突变。
- 复杂页面脚本已移入独立 CMD 模块，View 只安全注入配置并启动模块。
- 复杂页面已按可见业务板块拆分为同语义构造方法，每个板块只实例化一次，跨板块只调用实例返回的公开 API 或回调。
- 有初始化工作的板块已通过内部私有 `init()` 集中展示和执行启动流程，没有散落的初始化语句。
- 页面、Modal 和 AJAX 重复初始化不会重复绑定；关闭/替换时已执行 `destroy()`。
- 动态服务端文本未拼接进 HTML，页面不存在 `eval/new Function` 和隐式全局变量。
- 键盘/外部输入流程使用 `keydown/event.key`、输入焦点隔离、超时清空和请求乱序保护。
- 网络失败、快速连点、连续外部输入、可选媒体反馈不可用时均能恢复可操作状态。
- 顶部、侧栏、固定搜索、选中菜单、登录页及响应式布局正常。
- 控制台无 JavaScript 错误，网络无意外 404/500。
- 运行页面未加载 Bootstrap 3、AdminLTE 2、SwiftMailer、Socket.IO。

### 12.4 公共组件变更验收

修改 `admin/widgets`、`admin/helpers`、`admin/web/js`、`admin/web/css`、`common/widgets` 或 `vendor/mylibs` 时额外检查：

1. 首次完整页面加载。
2. iframe 内容页。
3. 页面弹窗。
4. AJAX 局部更新和重复初始化。
5. 空值、回显、禁用、只读和验证错误。
6. 所有已知调用方，而不是只验收报告问题的页面。
7. `assetVersion` 已按需递增。

### 12.5 禁止以“能用”作为完成标准

以下任一项存在即未完成：

- 为公共问题只修改单个业务 View。
- 新增旧框架 DOM、旧依赖或第二套前端库。
- View 查询数据库、引入业务层或直接比较状态常量。
- Controller/Service 绕过 LogicModel 改受保护状态。
- 使用错误事务模板或遗漏业务失败回滚。
- 把未运行的测试描述为通过。
- 提交临时测试文件、调试输出、凭据或用户未授权的范围外修改。

---

## 13. AI 快速自检清单

提交答案前逐条回答；任何“否”都必须修正或明确报告：

```text
[ ] 我只处理了用户指定范围，并保留了现有修改。
[ ] 我读取了真实 Schema、方法签名和已验收调用方，没有猜 API。
[ ] 我先检查了目标域已有 Controller、Model/Service、Form、View 和 common 领域层，没有把实现丢进根目录或单一文件。
[ ] 文件放在正确分层，下层没有反向依赖上层。
[ ] 所有 PHP 类和方法都有定义处 PHPDoc，方法内部没有可推断类型的重复 @var 注释。
[ ] Entity 只承担实体职责，LogicModel 承担业务动作和事务。
[ ] 我使用项目 Transaction 约定，没有 catch(Throwable)+isActive 模板。
[ ] Controller 只有输入、验证、调用、响应。
[ ] 列表查询按展示/筛选需要正确选择 with() 或 joinWith()，没有无依据的关联和字段裁剪。
[ ] View 没有引入领域/业务类，也没有直接比较状态常量。
[ ] Add 与 Modify 各自拥有完整 DOM，没有创建或调用共享 _form.php。
[ ] 页面复用了现有 Widget/Helper/公共 JS，没有复制组件实现。
[ ] 复杂交互使用独立 CMD 模块、根节点作用域、命名空间事件和 destroy()，View 没有大段全局脚本。
[ ] 复杂页面的每个业务板块都有能反映职责的构造方法；页面入口只 new 一次，板块之间没有跨区查 DOM 或修改私有状态。
[ ] 板块的默认状态、事件绑定、组件初始化和首次渲染集中在内部 init()，构造方法末尾只调用一次 init()。
[ ] PHP 数据通过 Json/Url Helper 注入，动态文本未拼接 HTML，代码没有 eval/new Function/隐式全局变量。
[ ] 顶部 TabMenu 与 Card Header 没有重复的列表、新增或返回入口，Header 未擅自增加操作按钮。
[ ] DOM、data 属性和类名符合 AdminLTE 4 + Bootstrap 5。
[ ] 公共问题修复在公共层，并回归了其他调用方。
[ ] 动态输出、SQL、上传、权限、CSRF 和敏感信息处理安全。
[ ] 我执行并如实报告了与风险相称的验证。
[ ] 没有提交临时文件、秘密、调试内容或用户明确删除的文件。
```

新项目若以本仓库为脚手架，必须完整复制本规范并随组件 API、依赖版本和已确认架构决策同步维护。README 不是事后说明；它是生成代码和代码审查的执行契约。

---

## 14. Console 应用开发规范

`console/` 是完整的 Yii Console 应用，不是临时脚本目录。命令代码必须进入 Controller、Service、Form、ImportModel 等既有分层，禁止把新 PHP 文件直接丢在 `console/` 根目录。

本节依据当前 `console/` 中的入口、配置、32 个 Controller、63 个 Model/Service/Form/ImportModel、报表统计、缓存清理、队列 Worker 和数据工具整理。历史文件用于识别业务能力，不代表其中的 Controller 重业务、`die()/var_dump()`、硬编码数据等写法可以继续复制。

### 14.1 目录的唯一职责

```text
console/
├── index.php                   # 唯一 CLI 应用入口，不放业务代码
├── config/                     # Console 应用及环境配置
├── controllers/               # 命令入口、参数接收、调用、输出和退出码
│   ├── caching/                # 缓存维护命令
│   ├── distribution/           # 加盟商定时业务命令
│   ├── order/                  # 订单定时业务命令
│   ├── report/                 # 报表生成命令
│   ├── statistics/             # 增量统计命令
│   ├── scm/                    # 供应链计划命令
│   └── tools/                  # 人工运维、修复、导入导出命令
├── models/                     # Console 应用层 Service、Form、Importer
│   ├── distribution/
│   ├── order/
│   ├── report/
│   ├── statistics/
│   ├── scm/
│   └── tools/
├── migrations/                 # 数据库迁移
└── runtime/                    # 日志、缓存、邮件等运行文件，仅保留 .gitignore
```

`console/` 根目录只允许框架入口及目录本身需要的固定文件。新增命令时禁止创建：

```text
console/SyncMember.php
console/member.php
console/TestCommand.php
console/cron_xxx.php
```

这类文件必须拆到 `console/controllers/<domain>` 和 `console/models/<domain>`。一次性维护任务也必须进入 `controllers/tools` 与对应 `models/tools`，任务结束后才能按用户要求删除，不能把根目录当作脚本垃圾箱。

AI 开始编写 Console 功能前，必须先执行以下定位步骤：

1. 根据业务域进入 `console/controllers/<domain>`，阅读同目录 Controller，确认现有路由、参数和日志方式。
2. 进入 `console/models/<domain>`，阅读与 Controller 同名或同用途的 Service、Form、ImportModel；不得只看 Controller 就开始生成。
3. 继续读取该 Service 调用的 `common/models/<domain>` Entity、LogicModel 和资源类，确认业务动作、状态和错误契约。
4. 优先扩展既有 Controller/Service；确需新文件时也必须成对确定入口层和实现层的位置。
5. 生成结果必须列出新增/修改的 Controller、Model/Service、common 领域文件和测试文件，不能只报告一个根目录脚本。

### 14.2 路径、命名空间和路由映射

| 文件 | 命名空间/基类 | CLI 路由 |
| --- | --- | --- |
| `console/controllers/order/OrderController.php` | `app\controllers\order`，继承 `app\controllers\Controller` | `order/order/<action-id>` |
| `console/models/order/OrderService.php` | `app\models\order`，继承 `app\models\Service` | 由同域 Controller 调用 |
| `console/controllers/tools/data/CarController.php` | `app\controllers\tools\data` | `tools/data/car/<action-id>` |
| `console/models/tools/data/car/CarService.php` | `app\models\tools\data\car` | 由 CarController 调用 |

Yii 将 `actionAutoCancel()` 映射为 `auto-cancel`。标准调用示例：

```text
php console/index.php order/order/auto-cancel
php console/index.php distribution/allocation/auto-receipt 7
php console/index.php tools/data/car/import-car-brake <file>
```

命名规则：

- Controller 使用 `<Feature>Controller.php`，Service 使用 `<Feature>Service.php`。
- 导入实现使用 `<Feature>ImportModel.php`，行输入使用 `<Feature>Form.php`。
- 子目录名表达业务域，类名保留当前功能语义，不使用 `Command.php`、`Task.php`、`Script.php` 等无法说明职责的万能名称。
- 文件路径、namespace、类名和 Controller 路由必须一一对应。
- Controller 与 Service 应位于相同的业务路径；禁止只创建 Controller 而把全部实现写在 Action 中。

### 14.3 Console Controller 的职责

Console Controller 统一继承 `app\controllers\Controller`。基础 Controller 的 `beforeAction()` 会按 Action 路由设置日志 category；`log()` 写业务进度日志，`error()` 输出红色错误、记录错误并返回非零退出码。

Action 只允许完成：

1. 接收 CLI 标量参数。
2. 做最基础的缺失参数判断；复杂参数交给 Form/Service 验证。
3. 实例化同域 Service 并调用一个明确用例。
4. 输出必要结果、记录开始/结束日志并返回退出码。

严格禁止在 Controller 中：

- 直接使用 ActiveRecord/Query Builder 执行业务查询或循环。
- 创建 `Transaction`、跨表保存、状态迁移、库存和资金处理。
- 打开/解析导入文件、拼报表、执行批量清理或编写统计 SQL。
- 直接构建微信、支付、AWS 等外部 SDK 业务流程。
- 使用 `die()`、`exit()`、`var_dump()`、无结构调试 `echo` 终止命令。
- 把数百行修复逻辑放进一个 Action。

当前 `WechatController`、`tools/UpdateController`、`tools/CountingController` 和 `tools/data/DataController` 中存在上述历史写法。它们只能维护，禁止作为新命令模板；任务涉及重构时应把业务逻辑下移到对应 Service/LogicModel/Exporter/Importer。

标准 Controller：

```php
<?php

namespace app\controllers\order;

use app\controllers\Controller;
use app\models\order\OrderService;

/**
 * 订单计划任务命令。
 */
class OrderController extends Controller
{
    /**
     * 自动取消超过付款时限的订单。
     *
     * @param int|null $hours 超时小时数；为空时使用系统配置
     * @return int Console 退出码
     */
    public function actionAutoCancel($hours = null)
    {
        $this->log('开始自动取消超时订单');

        $service = new OrderService();
        if (!$service->autoCancel($this, $hours)) {
            return $this->error($service->getError());
        }

        $this->log('自动取消超时订单完成');
        return 0;
    }
}
```

方法级 PHPDoc 必须包含命令目的；参数含义、格式、单位或默认来源不明显时写 `@param`；Action 必须返回可由调度器识别的退出码。失败时必须 `return $this->error(...)`，禁止只调用 `error()` 后继续返回成功。

### 14.4 Console Service 的职责

Console Service 统一放在 `console/models/<domain>` 并继承 `app\models\Service`。它是命令用例编排层，负责：

- 选择待处理数据并使用 `each()/batch()` 流式遍历。
- 调用 `common` 中的 Entity 查询、LogicModel 业务动作、Message、Cache、Job 或 Resource。
- 编排报表、统计、导入导出和运维用例。
- 汇总 `Error/LogicModel` 错误，决定单条失败后继续还是整个命令失败。
- 在长任务中输出可追踪的每项进度，但不输出敏感信息。

Console Service 禁止：

- 读取 `$_SERVER['argv']` 或自行解析 Yii Controller 参数。
- 使用 `die()/exit()/var_dump()` 控制流程。
- 直接向 STDOUT 输出无协议调试内容；输出由 Controller/Logger 负责。
- 复制 `common` LogicModel 已有的订单、库存、资金状态迁移。
- 依赖 Admin Controller、View、Widget、HTML 或 Web Request/Response。
- 把本可拆分的导入解析、行验证、业务写入全部塞进一个 Service 方法。

标准批处理 Service：

```php
<?php

namespace app\models\order;

use app\models\Service;
use common\models\order\Order;
use common\models\order\OrderModel;
use mylibs\DateTimeUtility;

/**
 * 订单计划任务应用服务。
 */
class OrderService extends Service
{
    /**
     * 自动取消超过付款时限的订单。
     *
     * @param object $logger 提供 log() 的 Console 日志对象
     * @param int|null $hours 超时小时数
     * @return bool 是否完成本次批处理
     */
    public function autoCancel($logger, $hours = null)
    {
        $hours = $hours ?? 3;
        $time = DateTimeUtility::hoursBefore($hours);
        $note = "超过{$hours}小时未付款，系统自动取消";

        $query = Order::find();
        $query->andWhere(['status' => Order::STATUS_11000]);
        $query->andWhere(['<', 'status_time', $time]);
        $query->orderBy(['id' => SORT_ASC]);

        foreach ($query->each() as $item) {
            $order = OrderModel::getById($item->id);
            if ($order === null) {
                $logger->log($item->id.'|false|订单不存在');
                continue;
            }
            if (!$order->cancel($note)) {
                $logger->log($item->number.'|false|'.$order->getError());
                continue;
            }
            $logger->log($item->number.'|success');
        }

        return true;
    }
}
```

上述 Service 负责“找到哪些记录并逐条调用领域动作”；真正的 `cancel()` 状态校验和事务仍在 `common` LogicModel。若任一失败必须停止，应 `setError/addErrors` 后返回 `false`；若允许逐条继续，必须记录业务 ID、结果和错误，并在下一条前清理当前 Service 的临时错误。

### 14.5 参数、Form 和错误契约

- 一个简单 ID、日期、小时数可作为 Action 参数，但必须在 Service 使用前归一化并校验范围。
- 多字段、跨字段规则或导入行必须创建 `console/models/<domain>/<Feature>Form.php`，继承 `common\models\FormModel`。
- Form 只负责输入规则和标签，不查询数据库、不保存实体。
- 业务失败使用 `setError()/addErrors()` 并返回 `false`；系统异常继续上抛给 Yii Console 错误处理器。
- 不得用 `catch (Throwable)` 吞掉异常后打印“执行成功”。
- 参数中包含日期、金额、状态、文件路径时，PHPDoc 必须说明格式和单位。

行 Form 示例：

```php
<?php

namespace app\models\tools\data;

use common\models\FormModel;

/**
 * 产品导入行输入模型。
 */
class ProductForm extends FormModel
{
    public $categoryName;
    public $supplierName;
    public $brandName;
    public $supplierNumber;

    /**
     * 返回导入行验证规则。
     */
    public function rules()
    {
        return [
            [['categoryName', 'supplierName', 'brandName', 'supplierNumber'], 'required'],
            [['supplierNumber'], 'string', 'max' => 64],
        ];
    }
}
```

### 14.6 数据导入和导出

数据工具固定分层：

```text
console/controllers/tools/data/<Feature>Controller.php
    ↓ 只接收文件路径并调用
console/models/tools/data/<Feature>Service.php 或 DataService.php
    ↓ 选择具体导入器
console/models/tools/data/<Feature>ImportModel.php
    ↓ 解析每行并装载
console/models/tools/data/<Feature>Form.php
    ↓ 验证后调用
common/models/<domain>/<Entity>Model.php
```

ImportModel 必须：

- 验证文件参数不为空、文件存在、可读、大小和允许目录；禁止任意路径访问。
- 使用流式读取，不把无限文件一次加载进内存。
- 明确编码、分隔符、列数、表头/注释行和空行规则。
- 维护真实行号；错误日志包含行号、可识别业务键和错误，不记录整行敏感数据。
- 每行装载专用 Form 并 `validate()`，禁止直接把 `$fields` 传给 Entity 保存。
- 明确“整批原子回滚”或“逐行提交并记录错误”策略，不能含糊混用。
- 文件打开成功后必须在统一结束位置显式 `fclose()`；不要为此引入项目已禁用的 `try/catch/finally` 模板。应把读取循环整理为单一可控出口，或复用已负责资源生命周期的公共 Importer。
- 把状态变更和多表写入交给 `common` LogicModel。

导出应创建专用 Exporter/Service，Controller 只传条件和输出目标。必须使用 `each()/batch()`，固定列顺序和编码，动态 SQL 值绑定参数。禁止像历史 `DataController` 一样把查询、行转换、N+1 和 `echo` 全写在 Action 中。

### 14.7 定时任务、统计和报表

| 类型 | Controller | Service | 额外要求 |
| --- | --- | --- | --- |
| 自动状态任务 | `order/`、`distribution/` | 同路径 Service | 调用 LogicModel 动作，禁止直接改状态 |
| 增量统计 | `statistics/` | Statistics Service | 使用断点/Tracking，顺序稳定，断点与统计写入同事务 |
| 周期报表 | `report/` | Report Service | 日期范围验证、幂等、报表头明细一致 |
| 缓存维护 | `caching/` | Cache 类或薄 Service | 只清目标 Key，禁止误清全库 |
| 队列 Worker | `JobController` | `mylibs\yii\job\Worker` | 长驻循环释放数据库连接、隔离任务身份 |
| 人工修复 | `tools/` | 专用 Service/Model | 默认提供 dry-run、范围和确认参数 |

计划任务必须：

- 可重复执行，重复运行不会重复扣减、重复发消息或生成重复报表。
- 查询使用确定性 `orderBy()`；大数据使用 `each()/batch()`。
- 断点只在当前记录所有写入成功后推进，并与结果写入同一事务。
- 单项失败策略明确：继续时记录失败并最终汇总；停止时返回非零退出码。
- 外部消息和网络调用设置超时、幂等键和重试边界。
- 防止同一命令并发重入；优先用调度平台单实例、数据库锁或安全 Mutex。
- 日志至少包含命令路由、开始/结束、耗时、处理数、成功数、失败数和业务键。

报表/统计 Service 可以维护自己的派生表，但不得绕过 LogicModel 改订单、库存、账户等源业务状态。原生 SQL 必须绑定值参数；动态表名、字段名和排序只来自白名单。

### 14.8 运维和数据修复命令

运维命令风险高于普通请求，新增时必须提供：

- 明确的作用范围参数，禁止默认扫描并修改全部生产数据。
- `--dry-run` 或等价预览模式，展示预计影响数量但不写入。
- 批次大小、最大处理数量或时间窗口。
- 幂等条件、旧状态条件和影响行数校验。
- 每批事务边界和失败恢复方法。
- 操作人、命令参数摘要、开始结束时间和结果审计。

严禁把真实业务 ID、订单号、公司名称、地址、系统用户 ID、日期和密钥硬编码到新命令。历史 `InstockOrderService`、`UpdateController` 等文件中的固定值仅是旧数据修复记录，不能复制到新项目。

破坏性命令（删除、余额修正、库存重算、批量状态变化）必须由用户明确授权。AI 不得因为代码放在 `tools/` 就自动执行。

### 14.9 Console 配置、身份和入口

- `console/index.php` 只负责确定环境、加载 Composer/Yii/common/console bootstrap、合并配置并运行 `yii\console\Application`。
- `console/config/bootstrap.php` 只设置 Console 所需 alias/bootstrap，不写业务逻辑。
- `console/config/main.php` 配置 `id`、`basePath`、`controllerNamespace`、组件和 params。
- `main-dev.php/main-test.php/main-prod.php` 只放环境差异；Gii 只能在明确的开发/测试环境启用。
- 本站数据库、缓存及共享开奖凭据统一放入被 Git 忽略的 `common/config/main-dev.php/main-test.php/main-prod.php`，不再叠加 local 配置；无密钥模板放在 `data/environment-templates/`。各入口保留直接 require 配置并使用 ArrayHelper 合并的方式，部署步骤见 `data/environment.README.md`。禁止把真实凭据写入 README、Controller、Service 或提交仓库。
- Console 身份通过 `app\models\SystemUser`/`SystemUserIdentity` 提供；系统操作人不能由 CLI 参数冒充。
- 长驻 Worker 每个任务开始前设置身份、结束后重置身份并关闭/重建失效数据库连接。
- `runtime/` 的 cache/log/mail 内容不提交，只保留 `.gitignore`。

### 14.10 Migration

- Schema 变化使用 `console/migrations`，文件名采用 Yii 时间戳规则。
- migration 只做可重复、可部署的结构/受控数据迁移，不调用 Controller/View。
- 新表明确主键、类型、默认值、非空、唯一索引、普通索引和外键策略。
- `down()` 无法安全回滚时明确返回 `false` 或说明原因，不能假装已回滚。
- 大数据回填与 Schema 变更分开；长耗时回填使用专用、可恢复的 Console 命令。

### 14.11 Console 文件生成决策表

| 需求 | 必须生成/修改 |
| --- | --- |
| 新增简单缓存清理命令 | `controllers/caching/<Feature>Controller.php`；复杂时增加同路径 Service |
| 新增订单自动处理 | `controllers/order/<Feature>Controller.php` + `models/order/<Feature>Service.php` + common LogicModel 方法/测试 |
| 新增加盟商计划任务 | `controllers/distribution/<Feature>Controller.php` + 对应 Service |
| 新增统计 | `controllers/statistics/<Feature>Controller.php` + `models/statistics/<Feature>Service.php` + Tracking/测试 |
| 新增周期报表 | `controllers/report/<Feature>Controller.php` + `models/report/<Feature>Service.php` + 报表 Entity/测试 |
| 新增文件导入 | tools/data Controller + Service + ImportModel + Form + common 领域写入 |
| 新增文件导出 | tools/data Controller + 专用 Exporter/Service |
| 新增一次性数据修复 | tools Controller + 专用 Service/Model + dry-run + 审计；不得放根目录 |
| 新增队列消费入口 | Controller 调用既有 Worker/Job；业务放 `common/jobs` |
| 新增表或索引 | `console/migrations` migration |

AI 不得把“新增 Console 功能”理解为“在 `console/` 下新建一个可执行 PHP 文件”。即使用户只说“做一个定时任务/导入脚本/修复脚本”，默认产物仍必须是本表对应的 Controller + Model/Service 分层；只有用户明确要求仓库外临时脚本时，才可另行处理，并且不得混入 `console/` 应用目录。

### 14.12 Console 验收门禁

新增或修改命令完成前逐项检查：

```text
[ ] console 根目录没有新增业务 PHP 文件。
[ ] Controller 和 Model/Service 位于相同业务路径，namespace 与路径一致。
[ ] Action 只有参数、调用、日志/输出和退出码，没有查询、事务、文件解析或业务写入。
[ ] 每个类和方法都有符合全局规范的 PHPDoc，方法体没有重复 @var 注释。
[ ] 复杂输入使用 Form，导入使用 ImportModel + 行 Form。
[ ] 状态、库存、资金等写入通过 common LogicModel。
[ ] 业务失败返回 false/Error，Action 返回非零退出码；没有 die/var_dump 调试流程。
[ ] 大数据使用 each/batch，查询顺序稳定，没有无界 all()。
[ ] 原生 SQL 值参数已绑定，动态标识符来自白名单。
[ ] 事务遵守项目 Transaction 规则，每个可预期失败分支显式 rollback()。
[ ] 命令可重入或有可靠互斥，断点和结果写入保持一致。
[ ] 导入路径、大小、编码、列数、行号、句柄关闭和错误策略已验证。
[ ] 运维写命令具有范围限制、dry-run、批次和审计。
[ ] 日志无密码、Token、私钥、完整个人信息和无界大文本。
[ ] 正常、空数据、非法参数、单项失败、整体失败、重复运行和并发场景已测试。
```

只要业务实现仍直接位于 `console/` 根目录，或者 Controller 内仍包含核心查询/循环/事务/导入/报表逻辑，就不能以“命令可以运行”作为完成标准。
