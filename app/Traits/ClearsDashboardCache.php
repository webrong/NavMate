<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait ClearsDashboardCache
{
    protected function clearDashboardCache(): void
    {
        Cache::forget('dashboard:stats');
        Cache::forget('dashboard:recent');
        Cache::forget('dashboard:top');
    }
}
