# NavMate — AI 开发指南

> 本文件是给 AI 助手（Claude / Cursor / Copilot 等）的项目记忆。
> 读完本文件，你就能快速上手这个项目，无需反复探索代码。

## 项目定位

NavMate 是一个开源的现代网址导航系统（类似 hao123），基于 Laravel 13 + Vue 3.5 构建。
GPL-3.0 许可证，免费开源。

- **仓库**：https://github.com/webrong/NavMate
- **演示站**：https://hao.wccto.com
- **当前版本**：见 `config/app.php` 的 `version` 字段（source-of-truth）

## 技术栈

| 层 | 技术 |
|----|------|
| 后端 | PHP **8.4** + Laravel **13** + Sanctum |
| 前端（前台） | Vue 3.5 + Vue Router 4 + Pinia 3 + Tailwind CSS 4 + Vite 8 |
| 后台 UI | antdv-next（Ant Design Vue）+ ECharts 6 |
| 数据库 | MySQL 5.7.22+ / 8.0+（缓存可选 Redis）|
| 安全 | DOMPurify（XSS）、CSP nonce、SSRF 防护 |

> ⚠️ PHP 必须 **8.4+**（composer.json 要求 `^8.4`）。模型已用 PHP 8.4 Attribute 注解（`#[Fillable]`、`#[Hidden]`）。

## 架构概览

```
浏览器
  ├─ /            → spa.blade.php  → 前台 Vue SPA
  └─ /admin/*     → admin.blade.php → 后台 Vue SPA
         │
   Laravel 路由 (routes/web.php)
         │
   公开 API (/api/*)          后台 API (/admin/api/*)
         │                          │
   Controllers → Services → Eloquent Models → MySQL
```

**双 SPA + 双认证**：`AdminUser`（后台）和 `User`（前台）是独立的表和 guard，互不干扰。

## 关键约定

### 后端

- **模型**：用 PHP 8.4 Attribute 注解（`#[Fillable]`），不是传统 `$fillable` 数组
- **Service 层**：`app/Services/` 下 6 个服务，控制器只做参数校验和响应，业务逻辑在 Service
- **Settings**：键值对存储在 `settings` 表，通过 `Setting::get()` / `Setting::set()` 读写（带缓存）。**不要读 `storage/app/settings.json`**（已废弃）
- **版本号**：source-of-truth 是 `config/app.php` 的 `version` + `storage/app/installed` marker 文件。**不要在 `composer.json` 里写 version 字段**（会触发 `composer validate --strict` 失败）
- **安装器**：`InstallerService` 全程不调用 `Artisan::call()`，专为 Windows + 内置服务器兼容
- **在线升级**：`UpdateService` 支持断点续传 + SHA256 校验 + 备份回滚 + **SSE 实时进度推送**
- **Cache::remember**：缓存闭包里**返回纯数组/标量**，不要缓存 Eloquent Collection（序列化/反序列化有问题，`->count` 会与 Model 方法冲突）

### 前端

- **两个 axios 实例**：`resources/js/utils/request.js`（前台）和 `resources/js/admin/utils/request.js`（后台），配置不同（CSRF、认证 guard）
- **动态 import 解耦**：`request.js` 用动态 `import('../stores/auth')` 规避循环依赖——这是刻意的代码分割，不是 bug
- **乐观更新 + 失败回滚**：stores（favorites/userLinks）的标准模式
- **后台 API 响应格式**：`{ code: 0, data: ... }`（仿 layui 风格）；分析/系统类接口用裸 `{ data: ... }`

### 数据库

- **21 个迁移文件**，`click_logs` 表增长最快（每次点击一条），有 `MassPrunable` 自动清理 90 天前数据
- **分类树**限 3 层深度，`CategoryTreeService::getPublicTree()` 用 `groupBy` 预加载避免 N+1
- **关键索引**：见 `2026_05_14_add_performance_indexes` 迁移

## 开发命令

```bash
# 开发模式（同时启动 Laravel + Vite）
composer run dev

# 代码风格检查（CI 会跑，提交前务必通过）
./vendor/bin/pint --test        # 检查
./vendor/bin/pint               # 自动修复

# 测试（用 sqlite :memory:，和 CI 一致）
php artisan test

# 三件套全绿 = 可推送
composer validate --strict --no-check-publish --no-check-lock
./vendor/bin/pint --test
php artisan test
```

> 本地 PHP 路径可能是 `C:\php-8.4.22\php.exe`（Windows 开发机）。

## CI/CD

| Workflow | 文件 | 触发 | 作用 |
|----------|------|------|------|
| **CI** | `.github/workflows/ci.yml` | push 到 master / PR | Pint + 测试 + 前端构建 |
| **Release** | `.github/workflows/release.yml` | push tag (v*) | 打包 vendor+前端 → 创建 GitHub Release + zip |

### 发版流程

```bash
# 1. 改版本号
# config/app.php: 'version' => '1.3.x',

# 2. 提交 + 打 tag + 推送
git add config/app.php
git commit -m "release: v1.3.x — 描述"
git tag -a v1.3.x -m "v1.3.x: 简述"
git push origin master
git push origin v1.3.x

# 3. Release workflow 自动打包发布（约 1-2 分钟）
```

### 在线升级机制（重要！）

- 升级修复代码本身需要通过发版才能到达用户站点
- 如果用户的在线升级坏了，必须**手动更新一次**到修复版本，之后在线升级才正常
- 升级包结构：zip 内含 `navmate-vX.X.X/` 根目录（`extractAndReplace` 自动检测）
- 升级保留：`.env`、`storage/`、`public/uploads`、`node_modules`

## 已知陷阱（踩过的坑）

> 完整日志（含现象/根因/修复/影响文件）见 **[`docs/PITFALLS.md`](docs/PITFALLS.md)**。
> 新踩的坑请追加到该文件顶部，并同步更新下面的摘要。

快速速记（最新在前）：

1. **仪表盘点击量缓存**：高频实时数据（clicks）不要和低频元数据（站点数）放同一缓存块，点击量实时查询
2. **SSE session 锁**：流式端点入口必须 `Session::save()` 释放锁
2. **缓存 Eloquent Collection**：`Cache::remember` 闭包返回纯数组，别缓存 Collection（`->count` 与 Model 方法冲突 → 500）
3. **断点续传损坏**：续传收到 200 时 cURL 追加到旧文件 → zip 损坏，需检测后重下
4. **在线升级鸡生蛋**：升级修复需发版 + 手动更新一次才能到达用户
5. **PHP `-r` 编码**：Windows cmd 下写 .php 文件执行，不用 `-r` 内联
6. **composer validate --strict**：`composer.json` 不能有 `version` 字段
7. **Pint fully_qualified_strict_types**：`catch (\Class $e)` 要改成 `use` 导入
8. **SSR SEO 数据源**：`View::composer('spa')` 读数据库 `Setting::allCached()`，不是 `settings.json`
9. **搜索越权**：私有站点不出现在公共搜索，不用可伪造的 `visitor_token`
10. **书签 slug 竞争**：并发导入捕获唯一约束冲突（1062）重试

## 目录速查

```
app/
├── Http/Controllers/
│   ├── Api/           # 前台 API（5 个：Auth/Category/Favorite/UserLayout/UserLink）
│   └── Admin/         # 后台 API（11 个控制器）
├── Models/            # 12 个模型
├── Services/          # 6 个服务（Installer/Update/Bookmark/UrlFetcher/SystemInfo/CategoryTree）
├── Middleware/        # 6 个中间件
└── Traits/            # ClearsDashboardCache

resources/js/
├── admin/views/       # 11 个后台页面
├── admin/utils/request.js  # 后台 axios 实例
├── components/        # 18 个前台组件
├── stores/            # 9 个 Pinia store
└── utils/request.js   # 前台 axios 实例

routes/web.php         # 所有路由（公开 API + 后台 API + SPA catch-all）
```

## 项目技能（.agents/skills/）

本地有 4 个技术栈技能（未提交到 git），可在 `.agents/skills/` 目录找到：
- `laravel-specialist` — Laravel 开发规范
- `vue-best-practices` — Vue 3 最佳实践
- `tailwind-design-system` — Tailwind 设计系统
- `vite` — Vite 构建配置
