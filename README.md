# NavMate

> Your web navigation companion.

**在线演示**：[https://hao.wccto.com](https://hao.wccto.com)

![NavMate 截图](img/screenshot.jpeg)

NavMate 是一个基于 Laravel 13 + Vue 3.5 的现代化网址导航系统，支持多主题、用户系统、后台管理、书签导入等功能。

## 技术栈

| 后端 | 前端 |
|------|------|
| PHP 8.3+ / Laravel 13.2 | Vue 3.5 + Vue Router 4 |
| Laravel Sanctum（API 认证） | Pinia 3（状态管理） |
| MySQL / SQLite | Tailwind CSS 4 |
| Redis（缓存/队列，可选） | Ant Design Vue（后台 UI） |
| Vite 8（构建工具） | DOMPurify（XSS 防护） |

## 功能特性

### 前台

- **分类导航** — 多级分类树，带图标展示
- **站内搜索** — 实时搜索 + 多引擎跳转（百度/Bing/Google/哔哩哔哩/淘宝等）
- **用户系统** — 注册、登录、邮箱验证、密码找回、头像上传
- **收藏夹** — 用户自定义收藏站点
- **快捷链接** — 用户自定义常用链接
- **布局设置** — 用户可调整站点布局偏好
- **主题切换** — 多主题支持（亮色/暗色）
- **响应式设计** — 移动端适配，侧边栏可折叠
- **SEO** — Sitemap、robots.txt、搜索引擎 Bot 预渲染
- **安装向导** — Web 端一键安装（环境检测、数据库配置）

### 后台管理

- **仪表盘** — 站点概览、统计数据
- **分类管理** — 增删改查、树形排序
- **站点管理** — 增删改查、URL 自动抓取标题/描述/图标
- **用户管理** — 用户列表、状态管理
- **数据分析** — 点击趋势、热门站点、时段分布
- **书签导入** — 支持 HTML/JSON 格式导入浏览器书签
- **友情链接** — 管理友情链接
- **系统设置** — 站点名称、公告、SEO、邮件等配置
- **系统监控** — 服务器信息、缓存清理
- **在线更新** — 内置版本更新机制（传统部署可用）

## 目录结构

```
app/
├── Console/Commands/     # Artisan 命令（安装、迁移设置、更新）
├── Http/
│   ├── Controllers/
│   │   ├── Api/          # 用户 API（认证、分类、收藏、布局、链接）
│   │   └── Admin/        # 后台 API（分析、书签导入、分类、仪表盘、友链、设置、站点、系统、用户）
│   └── Middleware/       # 中间件（管理员验证、安装检测、SEO预渲染、安全头、维护模式）
├── Models/               # Eloquent 模型（11个）
├── Providers/            # 服务提供者
└── Services/             # 业务服务（书签解析、分类树、安装器、系统信息、更新、URL抓取）

resources/
├── css/                  # Tailwind CSS
├── js/
│   ├── admin/            # 后台 Vue SPA
│   │   ├── components/   # AdminLayout.vue
│   │   ├── stores/       # adminAuth, adminCategories, adminDashboard, adminSites, adminUsers
│   │   ├── views/        # 10 个管理页面
│   │   └── router/       # 后台路由
│   ├── components/       # 前台组件（18个）
│   │   ├── auth/         # 认证弹窗（6个）
│   │   ├── ContentSection.vue
│   │   ├── SearchBar.vue
│   │   ├── SiteCard.vue
│   │   ├── TheHeader.vue / TheSidebar.vue / TheFooter.vue
│   │   ├── ThemeSwitcher.vue
│   │   ├── ToastNotifications.vue
│   │   └── ...
│   ├── composables/      # useSanitize, useSeo, useTheme
│   ├── stores/           # Pinia stores（8个）
│   ├── utils/            # lunar（农历）, request（HTTP）
│   ├── views/            # 页面组件（7个）
│   ├── router/           # 前台路由
│   └── App.vue           # 根组件
└── views/
    ├── spa.blade.php     # 前台 SPA 入口
    └── admin.blade.php   # 后台 SPA 入口

routes/
└── web.php               # 所有路由定义

database/
└── migrations/           # 19 个迁移文件
```

## 路由概览

### 公开页面
| 路径 | 说明 |
|------|------|
| `/` | 前台首页（SPA） |
| `/about` | 关于本站 |
| `/terms` | 使用条款 |
| `/sitemap.xml` | 站点地图 |
| `/robots.txt` | 搜索引擎指令 |

### 公开 API
| 路径 | 说明 |
|------|------|
| `GET /api/categories` | 分类列表 |
| `GET /api/settings` | 站点设置 |
| `GET /api/friend-links` | 友情链接 |
| `GET /api/search?q=` | 站内搜索 |
| `POST /api/fetch-url` | 抓取 URL 元数据 |
| `POST /api/click` | 点击记录 |

### 用户 API（需认证）
| 路径 | 说明 |
|------|------|
| `POST /api/register` | 注册 |
| `POST /api/login` | 登录 |
| `POST /api/logout` | 登出 |
| `POST /api/forgot-password` | 忘记密码 |
| `GET /api/user` | 当前用户 |
| `GET/POST /api/user/favorites` | 收藏管理 |
| `GET/PUT /api/user/layout` | 布局偏好 |
| `GET/POST /api/user/links` | 快捷链接 |
| `PUT /api/user/profile` | 更新资料 |
| `POST /api/user/avatar` | 上传头像 |

### 后台
| 路径 | 说明 |
|------|------|
| `/admin/login` | 管理员登录 |
| `/admin/api/dashboard` | 仪表盘数据 |
| `/admin/api/categories` | 分类 CRUD |
| `/admin/api/sites` | 站点 CRUD |
| `/admin/api/users` | 用户管理 |
| `/admin/api/analytics/*` | 数据分析 |
| `/admin/api/bookmarks/*` | 书签导入 |
| `/admin/api/settings` | 系统设置 |
| `/admin/api/friend-links` | 友情链接 |
| `/admin/api/system/*` | 系统操作 |

## 环境要求

- PHP >= 8.3（需启用 extensions：mbstring, xml, curl, mysqlnd/pdo_sqlite, zip, fileinfo）
- MySQL 5.7.22+ / 8.0+ / SQLite 3
- Node.js >= 18（仅构建时需要）
- Composer 2
- Nginx / Apache（生产环境）
- Redis（可选，用于缓存/队列）

---

## 部署方式一：Docker Compose（推荐）

适合快速部署，无需手动安装 PHP/MySQL/Nginx。

### 1. 创建 `docker-compose.yml`

```yaml
version: '3.8'
services:
  app:
    image: navmate/navmate:latest    # 或自行构建
    ports:
      - "8080:80"
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_URL: https://nav.example.com
      DB_HOST: mysql
      DB_PORT: 3306
      DB_DATABASE: navmate
      DB_USERNAME: navmate
      DB_PASSWORD: "your-strong-password"
      CACHE_DRIVER: database
      SESSION_DRIVER: database
      QUEUE_CONNECTION: database
    volumes:
      - app_storage:/var/www/html/storage
      - app_public:/var/www/html/public/uploads
    depends_on:
      mysql:
        condition: service_healthy
    restart: unless-stopped

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: "root-password"
      MYSQL_DATABASE: navmate
      MYSQL_USER: navmate
      MYSQL_PASSWORD: "your-strong-password"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      retries: 5
    restart: unless-stopped

volumes:
  app_storage:
  app_public:
  mysql_data:
```

### 2. 启动

```bash
docker compose up -d
```

### 3. 初始化

访问 `http://your-ip:8080`，按安装向导完成数据库和管理员配置。

### 4. 升级

```bash
docker compose pull
docker compose up -d
```

> **注意：** Docker 部署不支持后台在线升级功能，请通过拉取新镜像方式更新。

---

## 部署方式二：传统 PHP 部署

适合 VPS / 独立服务器，支持后台在线升级。

### 1. 安装系统依赖

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-fileinfo php8.3-gd nginx mysql-server

# CentOS/RHEL (Remi 仓库)
sudo dnf install -y php83 php83-php-fpm php83-php-mysqlnd php83-php-mbstring \
  php83-php-xml php83-php-curl php83-php-zip php83-php-gd nginx mysql-server
```

### 2. 克隆项目

```bash
cd /var/www
git clone https://github.com/yourname/navmate.git
cd navmate
```

### 3. 安装依赖

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 4. 初始化

```bash
cp .env.example .env
php artisan key:generate
```

> 数据库、Redis、邮件等配置无需手动编辑，首次访问时会进入安装向导，在网页上填写即可。

### 5. 设置权限

```bash
chown -R www-data:www-data /var/www/navmate
chmod -R 755 storage bootstrap/cache
```

### 6. 配置 Nginx

创建 `/etc/nginx/sites-available/navmate`：

```nginx
server {
    listen 80;
    server_name nav.example.com;
    root /var/www/navmate/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/navmate /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 7. 完成

访问 `https://nav.example.com` 进入安装向导，按提示完成数据库和管理员配置。

### 在线升级

在后台「系统管理 → 在线更新」页面可一键升级。需在 `.env` 中配置：

```env
UPDATE_GITHUB_REPO=yourname/navmate
# 或自定义更新源
# UPDATE_CUSTOM_SOURCE=https://example.com/version.json
```

---

## 部署方式三：宝塔面板

适合不熟悉命令行的用户。

### 1. 安装宝塔面板

```bash
# 官方安装脚本
curl -sSO https://raw.githubusercontent.com/zhucaidan/btpanel-v7.7.0/main/install/install_panel.sh && bash install_panel.sh
```

### 2. 在宝塔中安装软件

在「软件商店」中安装：
- **Nginx** 1.24+
- **PHP-8.3**（安装后点击「设置 → 安装扩展」，确保 `fileinfo`、`mbstring`、`openssl`、`pdo_mysql`、`tokenizer`、`xml`、`curl`、`zip`、`gd` 已启用）
- **MySQL** 8.0（或 5.7）

### 3. 创建网站

1. 「网站」→「添加站点」
   - 域名：`nav.example.com`
   - PHP 版本：**PHP-83**
   - 数据库：**MySQL**，数据库名和密码记下来
   - 不要勾选「创建 FTP」

### 4. 部署代码

```bash
cd /www/wwwroot/nav.example.com

# 清空默认文件
rm -rf *

# 克隆项目
git clone https://github.com/yourname/navmate.git .
```

### 5. 安装依赖

```bash
# 如果服务器有 Node.js
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 如果没有 Node.js，在本地构建后上传 public/build/ 目录
```

### 6. 配置环境

```bash
cp .env.example .env
php artisan key:generate
```

> 无需手动编辑数据库配置，安装向导会引导你填写。

### 7. 设置权限

在宝塔「文件」管理器中，右键 `/www/wwwroot/nav.example.com` 目录：
- 权限设为 **755**
- 所有者设为 **www**

或在终端执行：

```bash
chown -R www:www /www/wwwroot/nav.example.com
chmod -R 755 storage bootstrap/cache
```

### 8. 配置 Nginx 伪静态

在宝塔「网站」→ 点击站点名 →「伪静态」，粘贴：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

保存即可。

### 9. 完成

访问域名进入安装向导，按提示完成。

---

## 部署方式四：1Panel（Docker 面板）

适合喜欢现代化面板的用户。1Panel 使用 Docker 容器管理运行环境，以下为完整部署流程。

### 1. 安装 1Panel

```bash
curl -sSL https://resource.fit2cloud.com/1panel/package/quick_start.sh -o quick_start.sh && bash quick_start.sh
```

### 2. 安装运行环境

在 1Panel「应用商店」中安装：
- **OpenResty**（Web 服务器）
- **MySQL** 5.7+ 或 8.0

> MySQL 5.7.22+ 兼容本项目（JSON 列支持）。

### 3. 创建网站

「网站」→「创建网站」→ 选择「运行环境」：
- 主域名：`nav.example.com`
- 运行环境：**PHP 8.4**（如无此选项，选 8.3 并在容器中升级）
- 根目录保持默认即可

> 1Panel 会自动创建 PHP 容器和 OpenResty 反向代理配置。

### 4. 安装 PHP 扩展

进入 1Panel「容器」→ 找到 PHP 容器（通常名为 `navmate-php`）→ 点击「终端」：

```bash
# 更新包源
apt update

# 安装必要的 PHP 扩展（以 PHP 8.4 为例，根据实际版本调整包名）
apt install -y php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
  php8.4-zip php8.4-fileinfo php8.4-gd php8.4-bcmath

# 验证扩展是否加载
php -m | grep -E 'pdo_mysql|mbstring|xml|curl|zip|fileinfo|gd'
```

> **必须包含 `pdo_mysql`**，否则数据库连接会失败。

### 5. 部署代码

在 PHP 容器终端中操作：

```bash
# 进入网站根目录（容器内路径，不是宿主机路径）
cd /www/sites/navmate/index

# 如果是空目录，克隆项目
git clone https://github.com/webrong/NavMate.git .

# 安装 PHP 依赖
composer install --no-dev --optimize-autoloader
```

> 如果服务器没有 Node.js，在本地执行 `npm install && npm run build`，然后将生成的 `public/build/` 目录上传到服务器。

### 6. 环境配置

继续在 PHP 容器终端中：

```bash
cp .env.example .env
php artisan key:generate
```

### 7. 设置权限

```bash
# 容器内执行
chmod -R 755 storage bootstrap/cache public/uploads
```

如果权限不对，也可在 1Panel「文件」管理器中右键网站目录设置权限为 **755**。

### 8. 配置伪静态

在 1Panel「网站」→ 点击站点名 →「反向代理/伪静态」或「Nginx 配置」，确保包含：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 9. 完成安装

访问域名进入安装向导：
- **数据库主机**：填写 MySQL 容器名（如 `navmate-mysql`）或内网 IP
- **数据库端口**：默认 `3306`
- **缓存驱动**：如未安装 Redis，选择 **Database**
- **Session 驱动**：同上，选择 **Database**

> 安装向导会自动检测环境、创建数据表和管理员账号。

### 常见问题

**Q: 安装向导「下一步」按钮点击无效（页面刷新但不跳转）**
- 浏览器控制台如显示 CSP（Content-Security-Policy）错误，请确保使用最新版本代码，已修复此问题。

**Q: 连接数据库失败 `Access denied`**
- 确认数据库用户有远程访问权限（1Panel MySQL 容器默认支持）。
- 数据库主机应填写容器名或内网 IP，不要填 `localhost`。

**Q: `Class "App\Services\PDO" not found`**
- 容器内未安装 `pdo_mysql` 扩展，参考第 4 步安装。

**Q: 1Panel 定时任务执行 Artisan 命令失败**
- 1Panel 任务执行器会自动在命令前加 `composer` 前缀，导致命令错误。
- 请直接在 PHP 容器终端中执行 Artisan 命令。

**Q: 页面出现 500 错误**
- 进入 PHP 容器终端执行 `php artisan config:clear` 清除配置缓存。
- 检查 `.env` 文件中的 `DB_DATABASE` 等配置是否正确。

---

## 开发模式

```bash
# 同时启动 Laravel 和 Vite 开发服务器
composer run dev

# 或分别启动
php artisan serve
npm run dev
```

## 数据模型

```
User ─┬── Favorite ──── Site
      ├── UserLayout
      ├── UserLink
      └── avatar (文件)

AdminUser ─── 独立管理员认证

Category ─┬── parent_id (自关联)
          └── Site ──── ClickLog

FriendLink ─── 友情链接
Setting ─── 系统设置（键值对）
UpdateLog ─── 更新日志
```

## 许可证

[GPL 3.0](LICENSE) © 2026 NavMate

> NavMate 是开源免费项目，如果你是从任何渠道付费获取的，请要求退款。
> 官方获取地址：https://github.com/webrong/NavMate
