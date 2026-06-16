<?php

namespace App\Services;

use App\Models\UpdateLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class UpdateService
{
    protected string $repo;

    protected ?string $customSource;

    public function __construct()
    {
        $this->repo = config('services.update.github_repo', '');
        $this->customSource = config('services.update.custom_source');
    }

    /**
     * Get the currently installed version
     */
    public function getCurrentVersion(): string
    {
        $path = storage_path('app/installed');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);

            return $data['version'] ?? config('app.version', '1.0.0');
        }

        // Fallback for a fresh install with no marker file yet — keep this in
        // sync with the git tag and config('app.version').
        return config('app.version', '1.0.0');
    }

    /**
     * Update the installed version in the marker file
     */
    public function setCurrentVersion(string $version): void
    {
        $path = storage_path('app/installed');
        $data = file_exists($path) ? json_decode(file_get_contents($path), true) : [];
        $data['version'] = $version;
        $data['updated_at'] = now()->toIso8601String();
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT), LOCK_EX);
    }

    /**
     * Resolve a CA certificate bundle for verifying HTTPS peers.
     *
     * Windows PHP builds ship without a CA bundle, so cURL/openssl can't
     * verify peers and fails with "unable to get local issuer certificate".
     * This method picks the best available source:
     *
     *   1. php.ini `openssl.cafile` / `curl.cainfo` (if user configured it)
     *   2. The bundled Mozilla CA bundle shipped with the app (cacert/cacert.pem)
     *   3. null — caller decides whether to skip verification (local only)
     *
     * @return string|null Absolute path to a PEM bundle, or null if none found.
     */
    protected function resolveCaBundle(): ?string
    {
        // 1. Honor an explicit php.ini configuration if present and readable.
        foreach (['openssl.cafile', 'curl.cainfo'] as $iniKey) {
            $path = trim((string) ini_get($iniKey));
            if ($path !== '' && is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        // 2. Bundled Mozilla CA bundle (kept up to date via the release
        //    workflow; see cacert/cacert.pem). Covers Windows hosts that
        //    haven't configured php.ini.
        $bundled = base_path('cacert/cacert.pem');
        if (is_file($bundled) && is_readable($bundled)) {
            return $bundled;
        }

        // 3. System default paths on Linux/macOS.
        foreach (['/etc/pki/tls/certs/ca-bundle.crt', '/etc/ssl/certs/ca-certificates.crt', '/usr/local/share/certs/ca-root-nss.crt'] as $sys) {
            if (is_file($sys) && is_readable($sys)) {
                return $sys;
            }
        }

        return null;
    }

    /**
     * Check for available updates
     */
    public function checkForUpdate(): array
    {
        try {
            $currentVersion = $this->getCurrentVersion();

            if ($this->customSource) {
                return $this->checkCustomSource($currentVersion);
            }

            if (empty($this->repo)) {
                return [
                    'has_update' => false,
                    'current_version' => $currentVersion,
                    'error' => '未配置更新源（UPDATE_GITHUB_REPO）',
                ];
            }

            return $this->checkGitHub($currentVersion);
        } catch (\Throwable $e) {
            return [
                'has_update' => false,
                'current_version' => $this->getCurrentVersion(),
                'error' => '检查更新失败: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Build an HTTP client preconfigured with a sensible CA bundle.
     *
     * Falls back to `verify=false` ONLY on local/dev environments where no
     * bundle is available at all — never in production. Verifying HTTPS peers
     * is mandatory for the update flow (it downloads an archive we then exec).
     */
    protected function httpClient(int $timeout = 10)
    {
        $options = ['timeout' => $timeout, 'verify' => true];

        $caBundle = $this->resolveCaBundle();
        if ($caBundle) {
            $options['verify'] = $caBundle;
        } elseif (app()->environment('local', 'testing')) {
            // Last resort for a dev box with zero CA bundle configured — don't
            // block local testing. We never do this in production.
            $options['verify'] = false;
        }

        return Http::withOptions($options);
    }

    /**
     * Check GitHub Releases API
     *
     * GitHub's API can return transient 5xx (502/503/504) under load or when
     * the network path to api.github.com is unstable (common from mainland
     * China). We retry up to 3 times with exponential backoff before giving
     * up, and surface a friendly "暂时性问题，稍后重试" message instead of
     * the raw "GitHub API 请求失败: 504".
     */
    protected function checkGitHub(string $currentVersion): array
    {
        $url = "https://api.github.com/repos/{$this->repo}/releases/latest";
        $headers = ['Accept' => 'application/vnd.github+json'];

        $response = null;
        $lastStatus = 0;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = $this->httpClient(20)
                    ->withHeaders($headers)
                    ->get($url);
                $lastStatus = $response->status();
                // 2xx → done. 4xx (auth/rate-limit/not-found) → no point retrying.
                if ($response->successful() || $lastStatus < 500) {
                    break;
                }
            } catch (\Throwable $e) {
                // Network error (cURL timeout, DNS, etc.) — retry.
                $lastStatus = 0;
            }
            if ($attempt < 3) {
                usleep(500000 * (2 ** ($attempt - 1))); // 0.5s, 1s
            }
        }

        if (! $response || ! $response->successful()) {
            // Map the failure to a user-actionable message.
            if ($lastStatus >= 500 || $lastStatus === 0) {
                return [
                    'has_update' => false,
                    'current_version' => $currentVersion,
                    'error' => 'GitHub 服务暂时不可用（'.
                        ($lastStatus ?: '网络超时').
                        '），通常是网络拥堵或 GitHub 服务器繁忙，请稍后重试。',
                ];
            }
            $errorMsg = 'GitHub API 请求失败: '.$lastStatus;
            if ($lastStatus === 403) {
                $remaining = $response?->header('X-RateLimit-Remaining');
                if ($remaining === '0') {
                    $reset = $response?->header('X-RateLimit-Reset');
                    $waitMinutes = $reset ? max(1, round(($reset - time()) / 60)) : 60;
                    $errorMsg = "GitHub API 请求次数已用完，请 {$waitMinutes} 分钟后再试";
                }
            }

            return [
                'has_update' => false,
                'current_version' => $currentVersion,
                'error' => $errorMsg,
            ];
        }

        $release = $response->json();
        $latestVersion = ltrim($release['tag_name'] ?? '', 'v');
        $hasUpdate = version_compare($currentVersion, $latestVersion, '<');

        // Find the release asset (zip)
        $downloadUrl = null;
        foreach ($release['assets'] ?? [] as $asset) {
            if (str_ends_with($asset['name'], '.zip')) {
                $downloadUrl = $asset['browser_download_url'];
                break;
            }
        }

        return [
            'has_update' => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'changelog' => $release['body'] ?? '',
            'download_url' => $downloadUrl,
            'release_url' => $release['html_url'] ?? '',
            'published_at' => $release['published_at'] ?? '',
        ];
    }

    /**
     * Check custom update source
     */
    protected function checkCustomSource(string $currentVersion): array
    {
        $scheme = parse_url($this->customSource, PHP_URL_SCHEME);
        if (! in_array($scheme, ['https', 'http'], true)) {
            return [
                'has_update' => false,
                'current_version' => $currentVersion,
                'error' => '更新源 URL 协议无效，仅支持 http/https',
            ];
        }

        $response = $this->httpClient(10)->get($this->customSource);

        if (! $response->successful()) {
            return [
                'has_update' => false,
                'current_version' => $currentVersion,
                'error' => '更新源请求失败: '.$response->status(),
            ];
        }

        $data = $response->json();
        $latestVersion = ltrim($data['version'] ?? '', 'v');
        $hasUpdate = version_compare($currentVersion, $latestVersion, '<');

        return [
            'has_update' => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'changelog' => $data['changelog'] ?? $data['description'] ?? '',
            'download_url' => $data['download_url'] ?? $data['archive'] ?? '',
            'published_at' => $data['published_at'] ?? '',
        ];
    }

    /**
     * Execute the update process
     */
    public function update(): array
    {
        $lockFile = storage_path('framework/update.lock');
        $lockHandle = fopen($lockFile, 'w+');
        if (! $lockHandle || ! flock($lockHandle, LOCK_EX | LOCK_NB)) {
            if ($lockHandle) {
                fclose($lockHandle);
            }

            return [
                'success' => false,
                'message' => '升级正在进行中，请稍后再试',
            ];
        }

        try {
            $result = $this->doUpdate();
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            @unlink($lockFile);
        }

        return $result;
    }

    protected function doUpdate(): array
    {
        // The upgrade touches thousands of files (vendor alone is 10k+) and
        // downloads an 11MB+ archive. PHP's default max_execution_time (30s)
        // will kill the process mid-extract or mid-copy, leaving the install
        // in a broken state and the UI stuck on the last logged step.
        // Remove the limit for the duration of the upgrade. (Safe here because
        // this method runs from an authenticated admin request, not cron.)
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $currentVersion = $this->getCurrentVersion();
        $log = '';
        $backupPath = null;
        $updateInfo = [];

        try {
            // 1. Check for update
            $log .= "[1/9] 检查更新...\n";
            $updateInfo = $this->checkForUpdate();

            if (! empty($updateInfo['error'])) {
                throw new \RuntimeException($updateInfo['error']);
            }

            if (empty($updateInfo['download_url'])) {
                throw new \RuntimeException('未找到可下载的发布包');
            }

            $targetVersion = $updateInfo['latest_version'];
            $downloadUrl = $updateInfo['download_url'];

            // Validate download URL scheme — HTTPS only to prevent MitM
            if (! str_starts_with($downloadUrl, 'https://')) {
                throw new \RuntimeException('下载地址必须使用 HTTPS 协议');
            }

            // 2. Enable maintenance mode (direct file write, no Artisan::call)
            $log .= "[2/9] 开启维护模式...\n";
            $this->enableMaintenanceMode();

            // 3. Create backup
            $log .= "[3/9] 备份当前文件...\n";
            $backupPath = $this->createBackup($currentVersion);

            // 4. Backup database
            $log .= "[4/9] 备份数据库...\n";
            $dbBackup = $this->backupDatabase();
            $log .= $dbBackup
                ? "    数据库已备份到: {$dbBackup}\n"
                : "    ⚠ 数据库备份跳过（mysqldump 不可用或备份失败）\n";

            // 5. Download release
            $log .= "[5/9] 下载新版本 {$targetVersion}...\n";
            $zipPath = $this->downloadRelease($downloadUrl);

            // 5.5 Verify integrity (SHA256 checksum) if provided by update source
            if (! empty($updateInfo['sha256'])) {
                $actualHash = hash_file('sha256', $zipPath);
                if (! hash_equals($updateInfo['sha256'], $actualHash)) {
                    @unlink($zipPath);
                    throw new \RuntimeException("下载包完整性校验失败（预期: {$updateInfo['sha256']}, 实际: {$actualHash}）");
                }
                $log .= "    SHA256 校验通过\n";
            } else {
                $log .= "    ⚠ 更新源未提供 SHA256 校验值，跳过完整性验证\n";
            }

            // 6. Extract and replace
            $log .= "[6/9] 解压并替换文件...\n";
            $this->extractAndReplace($zipPath);

            // 7. Update version
            $log .= "[7/9] 更新版本号...\n";
            $this->setCurrentVersion($targetVersion);

            // 8. Run migrations (via Migrator, no Artisan::call)
            $log .= "[8/9] 运行数据库迁移...\n";
            $this->runMigrations();

            // 9. Clear cache and disable maintenance mode
            $log .= "[9/9] 清除缓存并关闭维护模式...\n";
            $this->clearCache();
            $this->disableMaintenanceMode();

            // Clean up
            $this->cleanup($zipPath);

            // Log success
            UpdateLog::create([
                'from_version' => $currentVersion,
                'to_version' => $targetVersion,
                'status' => 'success',
                'log' => $log,
            ]);

            return [
                'success' => true,
                'message' => "升级成功！{$currentVersion} → {$targetVersion}",
                'from_version' => $currentVersion,
                'to_version' => $targetVersion,
            ];
        } catch (\Throwable $e) {
            $log .= "\n错误: ".$e->getMessage()."\n";

            // Try to restore from backup
            if ($backupPath && file_exists($backupPath)) {
                $log .= "正在从备份恢复...\n";
                try {
                    $this->restoreBackup($backupPath);
                    $log .= "已从备份恢复\n";
                } catch (\Throwable $restoreError) {
                    $log .= '备份恢复失败: '.$restoreError->getMessage()."\n";
                }
            }

            // Ensure maintenance mode is off
            try {
                $this->disableMaintenanceMode();
            } catch (\Throwable) {
            }

            // Log failure
            UpdateLog::create([
                'from_version' => $currentVersion,
                'to_version' => $updateInfo['latest_version'] ?? 'unknown',
                'status' => 'failed',
                'log' => $log,
            ]);

            return [
                'success' => false,
                'message' => '升级失败: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Enable maintenance mode (direct file write, no Artisan::call)
     */
    protected function enableMaintenanceMode(): void
    {
        $payload = json_encode([
            'retry' => 60,
            'refresh' => 5,
            'secret' => sha1(config('app.key')),
        ]);
        file_put_contents(storage_path('framework/down'), $payload);
    }

    /**
     * Disable maintenance mode
     */
    protected function disableMaintenanceMode(): void
    {
        $path = storage_path('framework/down');
        if (file_exists($path)) {
            @unlink($path);
        }
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
     * Clear compiled cache files
     */
    protected function clearCache(): void
    {
        $viewPath = storage_path('framework/views');
        if (is_dir($viewPath)) {
            array_map('unlink', glob($viewPath.'/*'));
        }

        foreach (['config.php', 'routes-v7.php', 'events.php', 'services.php'] as $file) {
            $path = base_path('bootstrap/cache/'.$file);
            if (file_exists($path)) {
                @unlink($path);
            }
        }

        // Clear application cache
        try {
            Cache::flush();
        } catch (\Throwable) {
            // Cache may not be available
        }
    }

    /**
     * Backup database using mysqldump if available
     */
    protected function backupDatabase(): ?string
    {
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $mysqlDump = exec(DIRECTORY_SEPARATOR === '\\' ? 'where mysqldump 2>NUL' : 'which mysqldump 2>/dev/null');
        if (empty($mysqlDump)) {
            return null;
        }

        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        $dbPort = config('database.connections.mysql.port', 3306);

        $backupFile = $backupDir.'/db-'.date('YmdHis').'.sql';
        $command = sprintf(
            '%s -h%s -P%s -u%s %s > %s 2>/dev/null',
            escapeshellcmd(trim($mysqlDump)),
            escapeshellarg($dbHost),
            escapeshellarg((string) $dbPort),
            escapeshellarg($dbUser),
            escapeshellarg($dbName),
            escapeshellarg($backupFile)
        );

        // Pass password via MYSQL_PWD env var to avoid exposure in process list
        $env = null;
        if ($dbPass) {
            $env = array_merge(getenv(), ['MYSQL_PWD' => $dbPass]);
        }

        $process = proc_open($command, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, null, $env);

        if (! is_resource($process)) {
            @unlink($backupFile);

            return null;
        }

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $returnCode = proc_close($process);
        if ($returnCode === 0 && file_exists($backupFile)) {
            return $backupFile;
        }
        @unlink($backupFile);

        return null;
    }

    /**
     * Create a backup of the current application files
     */
    protected function createBackup(string $version): string
    {
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupPath = $backupDir."/pre-{$version}-".date('YmdHis').'.zip';
        $zip = new ZipArchive;

        if ($zip->open($backupPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('无法创建备份文件');
        }

        $base = base_path();
        $exclude = ['vendor', 'node_modules', '.git', 'storage/app/backups', 'storage/framework/sessions', 'storage/framework/views'];

        $this->addDirectoryToZip($zip, $base, '', $exclude);
        $zip->close();

        return $backupPath;
    }

    /**
     * Recursively add directory to zip archive
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $basePath, string $prefix, array $exclude): void
    {
        $dir = $basePath.($prefix ? '/'.$prefix : '');
        if (! is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $relativePath = $prefix ? "{$prefix}/{$item}" : $item;

            // Skip excluded directories
            foreach ($exclude as $exc) {
                if (str_starts_with($relativePath, $exc) || $relativePath === $exc) {
                    continue 2;
                }
            }

            $fullPath = "{$basePath}/{$relativePath}";
            if (is_dir($fullPath)) {
                $this->addDirectoryToZip($zip, $basePath, $relativePath, $exclude);
            } else {
                $zip->addFile($fullPath, $relativePath);
            }
        }
    }

    /**
     * Download release archive
     *
     * Uses cURL with HTTP Range requests to resume partial downloads. This is
     * essential for hosts with slow or unstable connectivity to GitHub's
     * release-assets CDN, where a single streaming GET can exceed the timeout
     * before the file finishes. Each retry continues from the bytes already on
     * disk, so a flaky connection can still complete over several attempts.
     */
    protected function downloadRelease(string $url): string
    {
        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = $tmpDir.'/update-'.uniqid('', true).'.zip';

        $attemptTimeout = 300;  // wall-clock per attempt (seconds)
        $connectTimeout = 30;
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $resumeFrom = is_file($zipPath) ? (@filesize($zipPath) ?: 0) : 0;

            // When resuming we send a Range header and expect a 206 response.
            // If the server ignores Range and returns a full 200 instead, the
            // complete body gets written after the bytes we already had,
            // corrupting the archive (old prefix + full new file). We detect
            // that case below (httpCode 200 + resumeFrom > 0) and start over.
            $mode = $resumeFrom > 0 ? 'ab' : 'wb';
            $outHandle = fopen($zipPath, $mode);
            if ($outHandle === false) {
                throw new \RuntimeException('无法打开下载临时文件: '.$zipPath);
            }

            // Capture the response headers so we know whether we got 200 (full)
            // or 206 (partial), and the total size from Content-Range.
            $totalSize = null;       // total bytes of the resource, from Content-Range
            $isPartial = false;      // whether this response was a 206 chunk
            $headers = [];

            $ch = curl_init($url);
            $curlOptions = [
                CURLOPT_FILE => $outHandle,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $attemptTimeout,
                CURLOPT_CONNECTTIMEOUT => $connectTimeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HEADERFUNCTION => function ($curl, $header) use (&$headers, &$totalSize, &$isPartial) {
                    $headers[] = $header;
                    // Content-Range: bytes 5412992-12059402/12059403
                    if (preg_match('/^content-range:\s*bytes\s+\d+-\d+\/(\d+)/i', $header, $m)) {
                        $totalSize = (int) $m[1];
                        $isPartial = true;
                    }

                    return strlen($header);
                },
            ];

            // Use the resolved CA bundle so downloads verify peers correctly
            // on Windows hosts that have no system CA bundle configured.
            $caBundle = $this->resolveCaBundle();
            if ($caBundle) {
                $curlOptions[CURLOPT_CAINFO] = $caBundle;
            } elseif (app()->environment('local', 'testing')) {
                // Dev fallback only — never in production.
                $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
                $curlOptions[CURLOPT_SSL_VERIFYHOST] = 0;
            }
            curl_setopt_array($ch, $curlOptions);

            if ($resumeFrom > 0) {
                curl_setopt($ch, CURLOPT_RANGE, "{$resumeFrom}-");
            }

            curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = curl_errno($ch);
            $errorMsg = curl_error($ch);
            fclose($outHandle);
            curl_close($ch);

            clearstatcache(true, $zipPath);
            $currentSize = @filesize($zipPath) ?: 0;

            // Case 1: cURL error (timeout, connection reset, etc).
            // A timeout (28) or partial-transfer error (18) leaves a truncated
            // but otherwise valid prefix — safe to resume. Other errors abort.
            // On a timeout the HTTP code is unreliable (may be 0), so we must
            // not fall through to the HTTP-code checks below; retry instead.
            if ($errno !== 0) {
                $recoverable = in_array($errno, [28, 18, 56, 52, 53, 55], true);
                if (! $recoverable || $attempt >= $maxAttempts) {
                    @unlink($zipPath);
                    throw new \RuntimeException(
                        '下载发布包失败（cURL 错误 '.$errno.'）：'.$errorMsg
                    );
                }
                usleep(500000);

                continue;
            }

            // Case 2: HTTP error (non-2xx). No point retrying the same URL.
            if ($httpCode !== 200 && $httpCode !== 206) {
                @unlink($zipPath);
                throw new \RuntimeException('下载发布包失败: HTTP '.$httpCode);
            }

            // Case 3: Server ignored our Range header and returned a full 200.
            // If we were resuming (resumeFrom > 0), cURL appended the complete
            // body AFTER the stale prefix, producing a corrupt file. We must
            // discard it and retry from scratch. (On the first attempt, 200 is
            // the normal "full download" case — file is correct as written.)
            if ($httpCode === 200) {
                if ($resumeFrom > 0) {
                    // Corrupted by append-on-full-response. Start over.
                    @unlink($zipPath);
                    if ($attempt >= $maxAttempts) {
                        throw new \RuntimeException(
                            '下载发布包失败：更新源不支持断点续传且多次重试未完成，请稍后重试。'
                        );
                    }
                    usleep(500000);

                    continue;
                }
                break; // first attempt, full 200 — done
            }

            // Case 4: 206 Partial Content. If we know the total size, check if
            // we've got the whole thing; otherwise loop to fetch more.
            if ($isPartial && $totalSize !== null && $currentSize >= $totalSize) {
                break; // complete
            }

            if ($attempt >= $maxAttempts) {
                $pct = $totalSize ? ' ('.round($currentSize / $totalSize * 100).'%)' : '';
                throw new \RuntimeException(
                    '下载发布包失败：已下载 '.$currentSize.' 字节'.$pct
                    .'，重试 '.$maxAttempts.' 次后仍未完成。'
                    .'通常是服务器访问 GitHub 较慢导致，请稍后重试。'
                );
            }
            usleep(500000);
        }

        // Final size sanity check (limit: 200MB)
        $maxBytes = 200 * 1024 * 1024;
        $fileSize = @filesize($zipPath);
        if ($fileSize !== false && $fileSize > $maxBytes) {
            @unlink($zipPath);
            throw new \RuntimeException('下载文件过大（'.round($fileSize / 1024 / 1024, 1).'MB），超过 200MB 限制');
        }

        // Integrity self-check: open the archive with ZipArchive and make sure
        // it is valid. When the update source does not provide a SHA256
        // checksum (e.g. GitHub releases without a separately published digest),
        // this is our last line of defence — otherwise a truncated/corrupt
        // download only fails later in extractAndReplace with the opaque
        // "无法打开下载的压缩包", and by then we've already spent the backup.
        // We surface a clearer error and include the actual/expected sizes when
        // the server told us the total via Content-Range.
        $test = new ZipArchive;
        $openResult = $test->open($zipPath);
        if ($openResult !== true) {
            $sizeHint = '';
            if ($totalSize !== null) {
                $sizeHint = "（预期 {$totalSize} 字节，实际 {$fileSize} 字节，可能下载不完整）";
            } elseif ($fileSize !== false && $fileSize < 1024) {
                $sizeHint = "（文件仅 {$fileSize} 字节，可能是错误页面而非压缩包）";
            }
            @unlink($zipPath);
            $reason = $openResult === ZipArchive::ER_NOZIP
                ? '下载的文件不是有效的 ZIP 压缩包'
                : ($openResult === ZipArchive::ER_READ
                    ? '下载的压缩包读取失败，可能不完整'
                    : '下载的压缩包无法打开（代码 '.$openResult.'）');
            throw new \RuntimeException("{$reason}{$sizeHint}，请稍后重试。");
        }
        $test->close();

        return $zipPath;
    }

    /**
     * Extract archive and replace application files
     */
    protected function extractAndReplace(string $zipPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('无法打开下载的压缩包');
        }

        // Validate ZIP contents: block path traversal and absolute paths
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_contains($name, '..') || str_starts_with($name, '/')) {
                $zip->close();
                throw new \RuntimeException('压缩包包含非法路径: '.$name);
            }
        }
        $zip->close();

        // Re-open and extract
        $zip = new ZipArchive;
        $zip->open($zipPath);
        $tmpDir = storage_path('app/tmp/extract-'.uniqid('', true));
        if (! mkdir($tmpDir, 0755, true)) {
            throw new \RuntimeException('无法创建临时目录');
        }
        if (! $zip->extractTo($tmpDir)) {
            $zip->close();
            $this->recursiveDelete($tmpDir);
            throw new \RuntimeException('压缩包解压失败');
        }
        $zip->close();

        // Find the actual source directory (may be wrapped in a root folder)
        $sourceDir = $tmpDir;
        $entries = scandir($tmpDir);
        $dirs = array_values(array_filter($entries, fn ($e) => $e !== '.' && $e !== '..' && is_dir("{$tmpDir}/{$e}")));
        if (count($dirs) === 1) {
            $sourceDir = "{$tmpDir}/{$dirs[0]}";
        }

        // Validate source directory looks like a Laravel project
        if (! file_exists("{$sourceDir}/artisan") || ! file_exists("{$sourceDir}/composer.json")) {
            $this->recursiveDelete($tmpDir);
            throw new \RuntimeException('压缩包目录结构无效：未检测到有效的 Laravel 项目');
        }

        // Files/directories to preserve
        $preserve = ['.env', 'storage', 'public/uploads', 'node_modules'];

        $this->recursiveCopy($sourceDir, base_path(), $preserve);

        // Clean up
        $this->recursiveDelete($tmpDir);
    }

    /**
     * Recursively copy files from source to destination, skipping preserved paths
     */
    protected function recursiveCopy(string $src, string $dst, array $preserve, string $relativePath = ''): void
    {
        $dir = opendir($src);
        if ($dir === false) {
            throw new \RuntimeException("无法打开目录: {$src}");
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $currentRelative = $relativePath !== '' ? "{$relativePath}/{$file}" : $file;

            // Skip preserved items (match full relative path, top-level name, or prefix)
            foreach ($preserve as $p) {
                if ($currentRelative === $p || $file === $p || str_starts_with($currentRelative.'/', $p.'/')) {
                    continue 2;
                }
            }

            $srcPath = "{$src}/{$file}";
            $dstPath = "{$dst}/{$file}";

            if (is_dir($srcPath)) {
                if (! is_dir($dstPath)) {
                    mkdir($dstPath, 0755, true);
                }
                $this->recursiveCopy($srcPath, $dstPath, $preserve, $currentRelative);
            } else {
                if (! @copy($srcPath, $dstPath)) {
                    throw new \RuntimeException("文件复制失败: {$currentRelative}");
                }
            }
        }
        closedir($dir);
    }

    /**
     * Restore from backup (preserving .env and storage/)
     */
    protected function restoreBackup(string $backupPath): void
    {
        $zip = new ZipArchive;
        if ($zip->open($backupPath) !== true) {
            throw new \RuntimeException('无法打开备份文件');
        }

        // Extract to temp dir first, then selectively copy back
        $tmpDir = storage_path('app/tmp/restore-'.uniqid('', true));
        mkdir($tmpDir, 0755, true);
        $zip->extractTo($tmpDir);
        $zip->close();

        $this->recursiveCopy($tmpDir, base_path(), ['.env', 'storage']);
        $this->recursiveDelete($tmpDir);
    }

    /**
     * Clean up temporary files
     */
    protected function cleanup(string $zipPath): void
    {
        @unlink($zipPath);
    }

    /**
     * Recursively delete a directory
     */
    protected function recursiveDelete(string $path): void
    {
        if (is_dir($path)) {
            $items = @scandir($path);
            if ($items !== false) {
                foreach ($items as $item) {
                    if ($item !== '.' && $item !== '..') {
                        $this->recursiveDelete("{$path}/{$item}");
                    }
                }
            }
            @rmdir($path);
        } elseif (file_exists($path)) {
            @unlink($path);
        }
    }
}
