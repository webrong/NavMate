<?php

namespace App\Console\Commands;

use App\Services\InstallerService;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'app:install
        {--db-host=127.0.0.1 : Database host}
        {--db-port=3306 : Database port}
        {--db-database= : Database name}
        {--db-username= : Database username}
        {--db-password= : Database password}
        {--cache-store=database : Cache driver (database, redis, file)}
        {--redis-host=127.0.0.1 : Redis host}
        {--redis-port=6379 : Redis port}
        {--redis-password= : Redis password}
        {--app-name=导航 : Application name}
        {--app-url=http://localhost : Application URL}
        {--admin-name=Admin : Admin username}
        {--admin-email= : Admin email}
        {--admin-password= : Admin password (min 8 chars)}
        {--seed : Seed sample navigation data}
        {--skip-mail : Skip mail configuration}';

    protected $description = 'Install the navigation site via CLI';

    public function handle(InstallerService $installer): int
    {
        if (InstallerService::isInstalled()) {
            $this->error('The application is already installed.');
            $this->info('Delete storage/app/installed to re-run the installer.');
            return 1;
        }

        $this->info('Starting installation...');

        // Validate required fields
        $adminEmail = $this->option('admin-email');
        $adminPassword = $this->option('admin-password');

        if (!$adminEmail) {
            $adminEmail = $this->ask('Admin email address');
        }
        if (!$adminPassword) {
            $adminPassword = $this->secret('Admin password (min 8 chars)');
        }

        if (strlen($adminPassword) < 8) {
            $this->error('Password must be at least 8 characters.');
            return 1;
        }

        // Test database connection
        $this->info('Testing database connection...');
        $result = $installer->testDatabase([
            'db_host' => $this->option('db-host'),
            'db_port' => (int) $this->option('db-port'),
            'db_database' => $this->option('db-database'),
            'db_username' => $this->option('db-username'),
            'db_password' => $this->option('db-password'),
        ]);

        if (!$result['success']) {
            $this->error('Database connection failed: ' . $result['message']);
            return 1;
        }
        $this->info('Database connection successful.');

        $data = [
            'db_host'        => $this->option('db-host'),
            'db_port'        => (int) $this->option('db-port'),
            'db_database'    => $this->option('db-database'),
            'db_username'    => $this->option('db-username'),
            'db_password'    => $this->option('db-password'),
            'cache_store'    => $this->option('cache-store'),
            'redis_host'     => $this->option('redis-host'),
            'redis_port'     => (int) $this->option('redis-port'),
            'redis_password' => $this->option('redis-password'),
            'app_name'       => $this->option('app-name'),
            'app_url'        => $this->option('app-url'),
            'admin_name'     => $this->option('admin-name'),
            'admin_email'    => $adminEmail,
            'admin_password' => $adminPassword,
            'seed_sample'    => $this->option('seed'),
            'skip_mail'      => $this->option('skip-mail'),
        ];

        $result = $installer->install($data);

        if ($result['success']) {
            $this->info('');
            $this->info('Installation complete!');
            $this->info("Admin login: {$data['app_url']}/admin/login");
            $this->info("Email: {$adminEmail}");
            return 0;
        }

        $this->error('Installation failed: ' . $result['message']);
        return 1;
    }
}
