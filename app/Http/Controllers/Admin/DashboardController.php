<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ClickLog;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        // Low-frequency counts (categories/sites) are cached for 5 min — they
        // only change when an admin adds/removes data, and ClearsDashboardCache
        // invalidates them on such mutations.
        $counts = Cache::remember('dashboard:counts', 300, function () {
            return [
                'categories' => Category::count(),
                'sites' => Site::count(),
                'public_sites' => Site::where('is_public', true)->count(),
                'private_sites' => Site::where('is_public', false)->count(),
            ];
        });

        // Click stats are queried live on every request — clicks change
        // constantly (every site visit) and admins expect the dashboard to
        // reflect them immediately, not a 5-min-old cached snapshot.
        $clickStats = [
            'total_clicks' => Site::sum('clicks'),
            'today_clicks' => ClickLog::whereDate('clicked_at', today())->count(),
        ];

        $stats = array_merge($counts, $clickStats);

        $recentSites = Cache::remember('dashboard:recent', 120, function () {
            // Convert to a plain array so the cache serializer (file/database)
            // doesn't choke on Eloquent Collection, which otherwise becomes
            // __PHP_Incomplete_Class on read-back and breaks the JSON response.
            return Site::with('category:id,name')
                ->latest()->take(10)->get()
                ->map(fn ($s) => $s->only(['id', 'title', 'url', 'favicon_url', 'clicks', 'created_at']) + [
                    'category_name' => $s->category?->name,
                ])->all();
        });

        // Top sites (ordered by clicks) cached for 1 min — short enough that
        // ranking shifts are visible quickly, long enough to avoid re-running
        // the join+order query on every page load.
        $topSites = Cache::remember('dashboard:top', 60, function () {
            return Site::with('category:id,name')
                ->orderBy('clicks', 'desc')->take(10)->get()
                ->map(fn ($s) => $s->only(['id', 'title', 'url', 'favicon_url', 'clicks', 'created_at']) + [
                    'category_name' => $s->category?->name,
                ])->all();
        });

        return response()->json([
            'stats' => $stats,
            'recent_sites' => $recentSites,
            'top_sites' => $topSites,
        ]);
    }
}
