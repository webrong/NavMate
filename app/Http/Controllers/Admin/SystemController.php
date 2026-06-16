<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\UpdateLog;
use App\Services\SystemInfoService;
use App\Services\UpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SystemController extends Controller
{
    public function __construct(
        private SystemInfoService $systemInfo,
        private UpdateService $updater,
    ) {}

    /**
     * Get system information
     */
    public function info(Request $request): JsonResponse
    {
        if ($request->query('refresh') === '1') {
            Cache::forget('system:info');
        }

        return response()->json($this->systemInfo->all());
    }

    /**
     * Check for available updates
     */
    public function checkUpdate(): JsonResponse
    {
        $result = $this->updater->checkForUpdate();

        return response()->json($result);
    }

    /**
     * Execute the update with live SSE progress streaming.
     *
     * Each upgrade step is pushed to the client as a Server-Sent Event so the
     * UI can render a real-time progress bar. The response is a stream, not a
     * normal JSON response, so we send headers manually and flush after each
     * event. The session is saved immediately to release its lock — otherwise
     * the long-running upgrade would block all other requests on the same
     * session (e.g. the admin navigating other pages during upgrade).
     */
    public function update(): StreamedResponse
    {
        // Release the session lock so the upgrade stream doesn't block other
        // admin requests sharing this session.
        if (session()->isStarted()) {
            session()->save();
        }

        return new StreamedResponse(function () {
            // SSE event helper: format + flush immediately so the client sees
            // each step as it happens (no output buffering).
            $sendEvent = function (string $event, array $data): void {
                echo "event: {$event}\n";
                echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";

                while (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            };

            $onProgress = function (int $step, int $total, string $message, string $status) use ($sendEvent): void {
                $sendEvent('step', [
                    'step' => $step,
                    'total' => $total,
                    'message' => $message,
                    'status' => $status,
                ]);
            };

            $result = $this->updater->update($onProgress);

            if ($result['success']) {
                $sendEvent('done', $result);
            } else {
                $sendEvent('error', $result);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no', // disable Nginx buffering for live SSE
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Get update logs
     */
    public function updateLogs(): JsonResponse
    {
        $logs = UpdateLog::orderByDesc('id')->limit(20)->get();

        return response()->json($logs);
    }

    /**
     * Clear all application caches.
     *
     * NOTE: Cache::flush() wipes the entire cache store, including the
     * RateLimiter counters used by login throttle (auth/admin login) and the
     * per-route throttle middleware. Clearing caches therefore momentarily
     * resets those counters — e.g. an IP mid brute-force would get a fresh
     * budget. This is acceptable for an admin-initiated, infrequent action
     * (counters rebuild within seconds as traffic resumes), but be aware this
     * is NOT a surgical invalidation.
     */
    public function clearCache(): JsonResponse
    {
        $results = [];

        // Application cache
        try {
            Cache::flush();
            $results[] = '应用缓存';
        } catch (\Throwable) {
        }

        // Settings cache
        try {
            Setting::flushCache();
            $results[] = '设置缓存';
        } catch (\Throwable) {
        }

        // Config cache
        try {
            Artisan::call('config:clear');
            $results[] = '配置缓存';
        } catch (\Throwable) {
        }

        // View cache
        try {
            Artisan::call('view:clear');
            $results[] = '视图缓存';
        } catch (\Throwable) {
        }

        // Route cache
        try {
            Artisan::call('route:clear');
            $results[] = '路由缓存';
        } catch (\Throwable) {
        }

        $cleared = implode('、', $results);

        return response()->json([
            'success' => true,
            'message' => "已清理: {$cleared}",
        ]);
    }
}
