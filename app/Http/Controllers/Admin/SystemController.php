<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UpdateLog;
use App\Services\SystemInfoService;
use App\Services\UpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
     * Execute the update
     */
    public function update(): JsonResponse
    {
        $result = $this->updater->update();
        return response()->json($result, $result['success'] ? 200 : 500);
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
     * Clear system info cache
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget('system:info');
        return response()->json(['success' => true, 'message' => '缓存已清除']);
    }
}
