<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemInfoService
{
    /**
     * Get all system information (cached for 2 minutes)
     */
    public function all(): array
    {
        return Cache::remember('system:info', 120, fn () => [
            'app' => $this->appInfo(),
            'php' => $this->phpInfo(),
            'database' => $this->databaseInfo(),
            'cache' => $this->cacheInfo(),
            'storage' => $this->storageInfo(),
            'queue' => $this->queueInfo(),
        ]);
    }

    /**
     * Application info
     */
    protected function appInfo(): array
    {
        $installed = $this->getInstalledInfo();

        return [
            'name' => config('app.name'),
            'version' => $installed['version'] ?? '1.0.0',
            'laravel_version' => app()->version(),
            'installed_at' => $installed['installed_at'] ?? null,
            'env' => config('app.env'),
            'debug' => config('app.debug'),
            'url' => config('app.url'),
            'timezone' => config('app.timezone'),
        ];
    }

    /**
     * PHP info
     */
    protected function phpInfo(): array
    {
        $extensions = get_loaded_extensions();
        sort($extensions);

        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'ini_path' => php_ini_loaded_file(),
            'extensions' => $extensions,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
        ];
    }

    /**
     * Database info
     */
    protected function databaseInfo(): array
    {
        try {
            $driver = config('database.default');
            $version = DB::selectOne('SELECT VERSION() as v')?->v ?? 'unknown';

            $dbName = config('database.connections.' . $driver . '.database');
            $sizeResult = DB::selectOne(
                "SELECT ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ?",
                [$dbName]
            );
            $sizeMb = $sizeResult?->size_mb ?? 0;

            $connections = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'");
            $connections = $connections?->Value ?? 0;

            $tables = [];
            $tableRows = DB::select(
                "SELECT TABLE_NAME as table_name, TABLE_ROWS as table_rows,
                        ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb
                 FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = ?
                 ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC",
                [$dbName]
            );
            foreach ($tableRows as $row) {
                $tables[$row->table_name] = [
                    'rows' => (int) $row->table_rows,
                    'size_mb' => (float) $row->size_mb,
                ];
            }

            return [
                'driver' => $driver,
                'version' => $version,
                'database' => $dbName,
                'size_mb' => (float) $sizeMb,
                'connections' => (int) $connections,
                'tables' => $tables,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Cache info
     */
    protected function cacheInfo(): array
    {
        $driver = config('cache.default');
        $info = ['driver' => $driver, 'status' => 'unknown'];

        if ($driver === 'redis') {
            try {
                $redis = app('redis');
                $redisInfo = $redis->info();

                $info['status'] = 'connected';
                $info['redis'] = [
                    'version' => $redisInfo['redis_version'] ?? 'unknown',
                    'used_memory' => $redisInfo['used_memory_human'] ?? 'unknown',
                    'max_memory' => $redisInfo['maxmemory_human'] ?? '0',
                    'connected_clients' => $redisInfo['connected_clients'] ?? 0,
                    'uptime_days' => isset($redisInfo['uptime_in_days']) ? (int) $redisInfo['uptime_in_days'] : 0,
                ];
            } catch (\Throwable) {
                $info['status'] = 'disconnected';
            }
        } else {
            $info['status'] = 'active';
        }

        return $info;
    }

    /**
     * Storage/disk info
     */
    protected function storageInfo(): array
    {
        $path = storage_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;
        $percent = $total > 0 ? round(($used / $total) * 100) : 0;

        return [
            'disk_total' => $this->formatBytes($total),
            'disk_used' => $this->formatBytes($used),
            'disk_free' => $this->formatBytes($free),
            'disk_percent' => $percent,
            'storage_path' => $path,
        ];
    }

    /**
     * Queue info
     */
    protected function queueInfo(): array
    {
        $driver = config('queue.default');
        $info = ['driver' => $driver, 'pending' => 0, 'failed' => 0];

        if ($driver === 'database') {
            try {
                $info['pending'] = DB::table('jobs')->count();
                $info['failed'] = DB::table('failed_jobs')->count();
            } catch (\Throwable) {
                // tables may not exist
            }
        }

        return $info;
    }

    /**
     * Get installed marker info
     */
    protected function getInstalledInfo(): array
    {
        $path = storage_path('app/installed');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true) ?: [];
        }
        return [];
    }

    /**
     * Format bytes to human readable
     */
    protected function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . $units[$i];
    }
}
