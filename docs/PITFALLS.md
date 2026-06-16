# 已知陷阱日志（Pitfalls Log）

> 本文件记录项目开发中踩过的所有坑。每条含**现象 → 根因 → 修复**。
> **新踩的坑请追加到顶部**（最新在前），并同步更新 `CLAUDE.md` 的摘要。
> 这是持续维护的活文档，不要删减历史条目。

---

## P10. SSE 流式端点的 session 锁阻塞（2026-06-16）

**现象**：升级进度条用 SSE 流式输出，但流开始后其他 admin 页面请求全部卡住（loading 不结束）。

**根因**：Laravel 默认 web 中间件含 `StartSession`，file/database driver 下会持有 session 锁。SSE 长连接（升级可能持续数分钟）一直持锁，同 session 的并发请求被阻塞。

**修复**：SSE 控制器入口立即 `session()->save()` 释放锁：
```php
if (session()->isStarted()) {
    session()->save();
}
```

**影响文件**：`app/Http/Controllers/Admin/SystemController.php`

---

## P9. 缓存 Eloquent Collection 导致 500（2026-06-16）

**现象**：`/api/analytics/hourly` 接口返回 500，其他分析接口正常。

**根因**：`Cache::remember` 缓存了 `->get()->keyBy('hour')` 的 Eloquent Collection，循环里访问 `->count` 时与 Eloquent Model 的 `count()` 方法产生歧义。反序列化后的 Model 行为不确定。

**修复**：缓存闭包里返回纯数组，避免缓存 Collection/Model：
```php
// 错误：缓存 Collection，->count 有歧义
return ClickLog::...->select(DB::raw('COUNT(*) as count'))->get()->keyBy('hour');
$count = $hours->get($i)?->count ?? 0;

// 正确：缓存纯数组
return ClickLog::...->select(DB::raw('COUNT(*) as cnt'))->pluck('cnt', 'hour')->toArray();
$count = $hourMap[$i] ?? 0;
```

**教训**：`Cache::remember` 的闭包里**永远返回可安全序列化的纯数据**（数组/标量），不要返回 Collection/Model。SQL 别名避免用 `count`（Model 保留方法名）。

**影响文件**：`app/Http/Controllers/Admin/AnalyticsController.php`

---

## P8. 断点续传被 200 响应损坏下载文件（2026-06-16）

**现象**：在线升级报「无法打开下载的压缩包」，下载步骤看起来成功但 zip 损坏。

**根因**：`downloadRelease` 用 `ab`（追加）模式 + Range 请求做断点续传。当服务器忽略 Range 返回完整 200 时，cURL 把完整内容追加到旧的部分文件后面 → 文件 = `[旧前缀] + [完整新文件]` = 损坏。原代码 200 分支直接 `break` 未检测此情况。

**修复**：检测到「200 + resumeFrom > 0」时删除文件从头重下；下载完成后用 `ZipArchive::open` 自检完整性。

**影响文件**：`app/Services/UpdateService.php`

---

## P7. 在线升级「鸡生蛋」困境（2026-06-16）

**现象**：修复了在线升级的 bug，但用户站点无法通过在线升级获得修复（因为在线升级本身就是坏的）。

**根因**：升级修复代码在 master 上但未发 release；用户站点跑旧版 UpdateService，下载新包时仍触发原 bug。

**修复**：必须先发新 release（含修复），用户手动更新一次到修复版本，之后在线升级才正常。

**教训**：升级系统的修复**必须通过发版 + 一次性手动更新**才能到达用户。README 的手动升级步骤要完善。

---

## P6. PHP 内联 `-r` 在 Windows cmd 下的编码问题（2026-06-16）

**现象**：`php -r "..."` 内联脚本在 Windows cmd 下中文乱码或 `$` 转义错误。

**根因**：cmd 的字符编码（GBK）与 PHP 脚本的 UTF-8 不兼容；`$` 变量在双引号内被 cmd 解释。

**修复**：复杂脚本写成 `.php` 文件再用 `php script.php` 执行，不用 `-r` 内联。

---

## P5. composer validate --strict 因 version 字段失败（2026-06-16）

**现象**：CI 的 `composer validate --strict` 步骤失败，但本地未察觉。

**根因**：`composer.json` 里的 `version` 字段触发 composer warning「recommended to leave it out」，`--strict` 将 warning 升级为 error。同时 lock 文件 content-hash 与 json 不同步。

**修复**：移除 `composer.json` 的 `version` 字段（版本真实来源是 `config/app.php`），重新生成 lock。

**教训**：项目版本号的唯一 source-of-truth 是 `config/app.php`，不要放 `composer.json`。

**影响文件**：`composer.json`、`composer.lock`

---

## P4. Pint fully_qualified_strict_types 规则（2026-06-16）

**现象**：CI 的 Pint 检查失败，本地看不到具体违规（日志需认证）。

**根因**：`catch (\Illuminate\Database\QueryException $e)` 用了全限定类名，Pint 的 `fully_qualified_strict_types` 要求 `use` 导入后简写。

**修复**：`use Illuminate\Database\QueryException;` + `catch (QueryException $e)`。

**教训**：本机有 PHP 时提交前一定跑 `./vendor/bin/pint --test`。本机 PHP 路径：`C:\php-8.4.22\php.exe`。

**影响文件**：`app/Services/BookmarkParserService.php`

---

## P3. SSR SEO 数据源读废弃的 settings.json（2026-06-16）

**现象**：管理员在后台配置的 site_description、百度/Google 验证码不出现在首屏 HTML 里。

**根因**：`AppServiceProvider` 的 `View::composer('spa')` 仍读 `storage/app/settings.json`（已废弃），新安装的项目此文件不存在 → `$settings=[]` → meta 全用默认值。

**修复**：改为读数据库 `Setting::allCached()->all()`，与 prerender 视图和公开 API 统一数据源。

**影响文件**：`app/Providers/AppServiceProvider.php`

---

## P2. 搜索接口通过伪造 cookie 越权（2026-06-16）

**现象**：搜索接口泄露私有站点信息。

**根因**：`SiteController::search()` 用 `Cookie::get('visitor_token')` 匹配私有站点，但 cookie 是客户端可控的，可伪造/泄露后枚举他人私有收藏。

**修复**：移除搜索里的 `visitor_token` 匹配，私有站点不出现在公共搜索。

**影响文件**：`app/Http/Controllers/SiteController.php`

---

## P1. 书签导入 slug 并发竞争（2026-06-16）

**现象**：并发书签导入时偶发失败，整个事务回滚。

**根因**：`generateSlug` 是「查 → 拼 → 创建」非原子流程，`categories.slug` 有唯一索引，并发时第二个 `Category::create` 抛 `QueryException`。

**修复**：捕获唯一约束冲突（MySQL 1062）后重试一次。

**影响文件**：`app/Services/BookmarkParserService.php`
