<?php

namespace App\Services;

use App\Models\AdminUser;
use App\Models\Setting;
use Database\Seeders\SampleDataSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class InstallerService
{
    /**
     * Check server environment requirements
     */
    public function checkEnvironment(): array
    {
        $checks = [];

        $checks[] = [
            'category' => 'PHP 版本',
            'items' => [
                [
                    'label' => 'PHP 版本 (>= 8.4)',
                    'value' => PHP_VERSION,
                    'pass' => version_compare(PHP_VERSION, '8.4.0', '>='),
                ],
            ],
        ];

        $requiredExts = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'curl', 'gd', 'fileinfo'];
        $extItems = [];
        foreach ($requiredExts as $ext) {
            $extItems[] = [
                'label' => $ext,
                'value' => extension_loaded($ext) ? '已安装' : '未安装',
                'pass' => extension_loaded($ext),
            ];
        }
        $checks[] = [
            'category' => 'PHP 扩展（必需）',
            'items' => $extItems,
        ];

        $optionalExts = ['redis', 'memcached'];
        $optItems = [];
        foreach ($optionalExts as $ext) {
            $optItems[] = [
                'label' => $ext,
                'value' => extension_loaded($ext) ? '已安装' : '未安装',
                'pass' => true,
                'optional' => true,
            ];
        }
        $checks[] = [
            'category' => 'PHP 扩展（可选）',
            'items' => $optItems,
        ];

        $dirs = [
            storage_path(),
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];
        $dirItems = [];
        foreach ($dirs as $dir) {
            $dirName = basename($dir);
            $fullPath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $dir);
            $dirItems[] = [
                'label' => $fullPath,
                'value' => is_writable($dir) ? '可写' : '不可写',
                'pass' => is_writable($dir),
            ];
        }
        $checks[] = [
            'category' => '目录权限',
            'items' => $dirItems,
        ];

        $allPass = true;
        foreach ($checks as $group) {
            foreach ($group['items'] as $item) {
                if (! $item['pass'] && empty($item['optional'])) {
                    $allPass = false;
                    break 2;
                }
            }
        }

        return ['checks' => $checks, 'all_pass' => $allPass];
    }

    /**
     * Test database connection
     */
    public function testDatabase(array $data): array
    {
        try {
            $dbName = $data['db_database'] ?? '';
            $dsn = sprintf(
                'mysql:host=%s;port=%s',
                $data['db_host'] ?? '127.0.0.1',
                $data['db_port'] ?? 3306
            );
            $pdo = new \PDO($dsn, $data['db_username'] ?? '', $data['db_password'] ?? '', [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5,
            ]);
            if ($dbName) {
                $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.str_replace('`', '``', $dbName).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $pdo->exec('USE `'.str_replace('`', '``', $dbName).'`');
            }

            $result = ['success' => true, 'message' => '数据库连接成功'];

            $existing = $this->detectExistingData($pdo);
            if ($existing) {
                $result['existing_data'] = $existing;
            }

            return $result;
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '连接失败: '.$e->getMessage()];
        }
    }

    /**
     * Detect if the database already has data
     */
    protected function detectExistingData(\PDO $pdo): ?array
    {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'categories'");
            $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);

            if (empty($tables)) {
                return null;
            }

            $info = [];

            foreach (['categories' => '个分类', 'sites' => '个站点', 'admin_users' => '个管理员', 'users' => '个用户'] as $table => $label) {
                try {
                    $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    if ($count > 0) {
                        $info[] = "{$count} {$label}";
                    }
                } catch (\Throwable) {
                }
            }

            if (empty($info)) {
                return null;
            }

            return [
                'has_data' => true,
                'summary' => $info,
                'safe_reinstall' => true,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Test Redis connection
     */
    public function testRedis(array $data): array
    {
        try {
            if (! extension_loaded('redis')) {
                return ['success' => false, 'message' => 'Redis 扩展未安装'];
            }

            $redis = new \Redis;
            $redis->connect($data['redis_host'] ?? '127.0.0.1', (int) ($data['redis_port'] ?? 6379), 5);

            if (! empty($data['redis_password'])) {
                $redis->auth($data['redis_password']);
            }

            $redis->ping();
            $redis->close();

            return ['success' => true, 'message' => 'Redis 连接成功'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '连接失败: '.$e->getMessage()];
        }
    }

    /**
     * Execute the full installation
     *
     * Key design: NO Artisan::call(), NO reloadConfig().
     * DB connection configured directly from user input ($data).
     * Safe for PHP built-in server on Windows.
     */
    public function install(array $data): array
    {
        @set_time_limit(300);

        try {
            // 1. Write .env file (for future requests / artisan commands)
            $this->writeEnvFile($data);

            // 2. Generate APP_KEY if needed (direct file write, no artisan)
            $this->generateAppKey();

            // 3. Configure DB connection directly from user input (not from .env)
            $this->configureDatabase($data);

            // 4. Run migrations (via Migrator, no artisan)
            $this->runMigrations();

            // 5. Create admin user
            $this->createAdmin($data);

            // 6. Seed sample data (direct, no artisan)
            if (! empty($data['seed_sample'])) {
                $this->runSeeder();
            }

            // 7. Save site settings
            $this->saveSettings($data);

            // 8. Create installed marker
            $this->createInstalledMarker();

            return ['success' => true, 'message' => '安装成功！'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => '安装失败: '.$e->getMessage()];
        }
    }

    /**
     * Configure database connection directly from user input
     */
    protected function configureDatabase(array $data): void
    {
        config(['database.connections.mysql' => [
            'driver' => 'mysql',
            'host' => $data['db_host'] ?? '127.0.0.1',
            'port' => (int) ($data['db_port'] ?? 3306),
            'database' => $data['db_database'] ?? '',
            'username' => $data['db_username'] ?? '',
            'password' => $data['db_password'] ?? '',
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]) : [],
        ]]);

        DB::purge();
    }

    /**
     * Generate APP_KEY directly (no Artisan::call)
     */
    protected function generateAppKey(): void
    {
        if (! empty(config('app.key'))) {
            return;
        }

        $key = 'base64:'.base64_encode(random_bytes(32));

        $envPath = base_path('.env');
        $content = file_get_contents($envPath);
        $content = preg_replace('/^APP_KEY=.*/m', 'APP_KEY='.$key, $content);
        file_put_contents($envPath, $content);

        config(['app.key' => $key]);
    }

    /**
     * Run migrations via Laravel Migrator (no Artisan::call)
     */
    protected function runMigrations(): void
    {
        $migrator = app('migrator');
        $migrator->setConnection(config('database.default'));

        if (! $migrator->repositoryExists()) {
            $migrator->getRepository()->createRepository();
        }

        $migrator->run([database_path('migrations')], [
            'pretend' => false,
            'step' => false,
        ]);
    }

    /**
     * Run sample data seeder directly (no Artisan::call)
     */
    protected function runSeeder(): void
    {
        $seeder = new SampleDataSeeder;
        $seeder->setContainer(app());
        $seeder->__invoke();
    }

    /**
     * Write configuration to .env file
     */
    public function writeEnvFile(array $data): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            copy(base_path('.env.example'), $envPath);
        }

        $content = file_get_contents($envPath);

        $replacements = [
            'APP_NAME' => $data['app_name'] ?? 'NavMate',
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $data['app_url'] ?? 'http://localhost',
            'APP_LOCALE' => 'zh_CN',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'] ?? '127.0.0.1',
            'DB_PORT' => $data['db_port'] ?? '3306',
            'DB_DATABASE' => $data['db_database'] ?? '',
            'DB_USERNAME' => $data['db_username'] ?? '',
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'CACHE_STORE' => $data['cache_store'] ?? 'database',
            'SESSION_DRIVER' => ($data['cache_store'] ?? 'database') === 'redis' ? 'redis' : 'database',
            'NAV_INSTALLING' => 'true',
        ];

        if (($data['cache_store'] ?? 'database') === 'redis') {
            $replacements['REDIS_HOST'] = $data['redis_host'] ?? '127.0.0.1';
            $replacements['REDIS_PORT'] = $data['redis_port'] ?? '6379';
            $replacements['REDIS_PASSWORD'] = $data['redis_password'] ?? 'null';
        }

        foreach ($replacements as $key => $value) {
            $escaped = (str_contains($value, ' ') || str_contains($value, '#') || preg_match('/[\s\'"\\\\]/', $value))
                ? '"'.addslashes($value).'"'
                : $value;

            if (preg_match("/^{$key}=/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$escaped}", $content);
            } elseif (preg_match("/^#\s*{$key}=/m", $content)) {
                $content = preg_replace("/^#\s*{$key}=.*/m", "{$key}={$escaped}", $content);
            } else {
                $content .= "\n{$key}={$escaped}";
            }
        }

        file_put_contents($envPath, $content);
    }

    /**
     * Create the admin user
     */
    protected function createAdmin(array $data): void
    {
        $username = $data['admin_username'] ?? 'admin';
        // Email is no longer used for admin login but the column is NOT NULL +
        // UNIQUE. Generate a unique placeholder when none is provided so
        // re-installs with the same username don't collide.
        $email = $data['admin_email'] ?? ($username.'-'.str()->random(8).'@localhost');

        AdminUser::updateOrCreate(
            ['username' => $username],
            [
                'name' => $data['admin_name'] ?? 'Admin',
                'username' => $username,
                'email' => $email,
                'password' => $data['admin_password'],
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * Save site and mail settings
     */
    protected function saveSettings(array $data): void
    {
        $settings = [
            'site_name' => $data['app_name'] ?? '导航',
        ];

        if (empty($data['skip_mail']) && ! empty($data['mail_host'])) {
            $settings['mail_host'] = $data['mail_host'];
            $settings['mail_port'] = (string) ($data['mail_port'] ?? 465);
            $settings['mail_encryption'] = $data['mail_encryption'] ?? 'ssl';
            $settings['mail_username'] = $data['mail_username'] ?? '';
            $settings['mail_from_address'] = $data['mail_from_address'] ?? '';
            $settings['mail_from_name'] = $data['mail_from_name'] ?? $data['app_name'] ?? '';

            if (! empty($data['mail_password'])) {
                $settings['mail_password'] = Crypt::encryptString($data['mail_password']);
            }
        }

        Setting::setMany($settings);
    }

    /**
     * Create the installed marker file
     */
    protected function createInstalledMarker(): void
    {
        file_put_contents(storage_path('app/installed'), json_encode([
            'installed_at' => now()->toIso8601String(),
            'version' => '1.0.0',
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Check if the app is already installed
     */
    public static function isInstalled(): bool
    {
        return file_exists(storage_path('app/installed'));
    }
}
