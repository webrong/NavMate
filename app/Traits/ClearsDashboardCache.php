<?php

namespace App\Traits;

use App\Services\CategoryTreeService;
use Illuminate\Support\Facades\Cache;

trait ClearsDashboardCache
{
    /**
     * Invalidate dashboard aggregate caches.
     *
     * Analytics results are cached separately in AnalyticsController (5 min
     * TTL) and intentionally NOT cleared here: they are computed from
     * click_logs, a high-write table, and accepting a short staleness window
     * is preferable to scanning/clearing many parameterised cache keys on
     * every site/category mutation.
     */
    protected function clearDashboardCache(): void
    {
        Cache::forget('dashboard:counts');
        Cache::forget('dashboard:recent');
        Cache::forget('dashboard:top');
        // Public category tree (shared by /api/categories and bot prerender)
        Cache::forget(CategoryTreeService::TREE_CACHE_KEY);
    }
}
