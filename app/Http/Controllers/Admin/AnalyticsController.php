<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClickLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * Cache TTL for aggregate analytics (seconds). Analytics queries scan the
     * click_logs table, which grows fast (one row per click), so caching the
     * computed aggregates keeps the analytics page responsive as data grows.
     * 5 minutes is an acceptable staleness window for click statistics.
     */
    private const CACHE_TTL = 300;

    private function getDays(Request $request): int
    {
        return min(max((int) $request->input('days', 30), 1), 365);
    }

    private function getLimit(Request $request, int $max = 100): int
    {
        return min(max((int) $request->input('limit', 20), 1), $max);
    }

    public function trends(Request $request): JsonResponse
    {
        $days = $this->getDays($request);

        $trends = Cache::remember("analytics:trends:{$days}", self::CACHE_TTL, function () use ($days) {
            $startDate = now()->subDays($days)->startOfDay();

            return ClickLog::where('clicked_at', '>=', $startDate)
                ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        });

        return response()->json(['code' => 0, 'data' => $trends]);
    }

    public function topSites(Request $request): JsonResponse
    {
        $days = $this->getDays($request);
        $limit = $this->getLimit($request);

        $topSites = Cache::remember("analytics:top_sites:{$days}:{$limit}", self::CACHE_TTL, function () use ($days, $limit) {
            $startDate = now()->subDays($days)->startOfDay();

            return ClickLog::where('clicked_at', '>=', $startDate)
                ->select('site_id', DB::raw('COUNT(*) as clicks'))
                ->groupBy('site_id')
                ->orderBy('clicks', 'desc')
                ->limit($limit)
                ->with('site:id,title,url,category_id')
                ->get();
        });

        return response()->json(['code' => 0, 'data' => $topSites]);
    }

    public function summary(Request $request): JsonResponse
    {
        $days = $this->getDays($request);

        $data = Cache::remember("analytics:summary:{$days}", self::CACHE_TTL, function () use ($days) {
            $startDate = now()->subDays($days)->startOfDay();

            $totalClicks = ClickLog::where('clicked_at', '>=', $startDate)->count();
            $uniqueIps = ClickLog::where('clicked_at', '>=', $startDate)->distinct('ip_address')->count();
            $avgDaily = $days > 0 ? round($totalClicks / $days, 1) : 0;

            // Today's clicks
            $todayClicks = ClickLog::where('clicked_at', '>=', now()->startOfDay())->count();

            // Previous period for comparison
            $prevStart = now()->subDays($days * 2)->startOfDay();
            $prevClicks = ClickLog::whereBetween('clicked_at', [$prevStart, $startDate])->count();
            $clickGrowth = $prevClicks > 0 ? round((($totalClicks - $prevClicks) / $prevClicks) * 100, 1) : 0;

            return [
                'total_clicks' => $totalClicks,
                'unique_visitors' => $uniqueIps,
                'avg_daily_clicks' => $avgDaily,
                'today_clicks' => $todayClicks,
                'click_growth' => $clickGrowth,
            ];
        });

        return response()->json(['code' => 0, 'data' => $data]);
    }

    public function topCategories(Request $request): JsonResponse
    {
        $days = $this->getDays($request);
        $limit = $this->getLimit($request, 50);

        $topCategories = Cache::remember("analytics:top_categories:{$days}:{$limit}", self::CACHE_TTL, function () use ($days, $limit) {
            $startDate = now()->subDays($days)->startOfDay();

            return ClickLog::where('clicked_at', '>=', $startDate)
                ->join('sites', 'click_logs.site_id', '=', 'sites.id')
                ->join('categories', 'sites.category_id', '=', 'categories.id')
                ->select('categories.id', 'categories.name', 'categories.icon', DB::raw('COUNT(*) as clicks'))
                ->groupBy('categories.id', 'categories.name', 'categories.icon')
                ->orderBy('clicks', 'desc')
                ->limit($limit)
                ->get();
        });

        return response()->json(['code' => 0, 'data' => $topCategories]);
    }

    public function hourlyDistribution(Request $request): JsonResponse
    {
        $days = $this->getDays($request);

        // Cache a plain hour=>count array (not a Collection) to avoid
        // Eloquent Model serialization issues and `->count` ambiguity (count
        // is also a Model method).
        $hourMap = Cache::remember("analytics:hourly:{$days}", self::CACHE_TTL, function () use ($days) {
            $startDate = now()->subDays($days)->startOfDay();

            return ClickLog::where('clicked_at', '>=', $startDate)
                ->select(DB::raw('HOUR(clicked_at) as hour'), DB::raw('COUNT(*) as cnt'))
                ->groupBy('hour')
                ->orderBy('hour')
                ->pluck('cnt', 'hour')
                ->map(fn ($v) => (int) $v)
                ->toArray();
        });

        $data = [];
        for ($i = 0; $i < 24; $i++) {
            $data[] = [
                'hour' => $i,
                'count' => $hourMap[$i] ?? 0,
            ];
        }

        return response()->json(['code' => 0, 'data' => $data]);
    }

    public function recentClicks(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);

        // Not cached — recent clicks are meant to be near-real-time.
        $clicks = ClickLog::with('site:id,title,url')
            ->orderBy('clicked_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'site_title' => $log->site?->title ?? '(已删除)',
                'site_url' => $log->site?->url ?? '',
                'ip_address' => $log->ip_address,
                'clicked_at' => $log->clicked_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json(['code' => 0, 'data' => $clicks]);
    }
}
