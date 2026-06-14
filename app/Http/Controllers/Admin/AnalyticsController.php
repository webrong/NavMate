<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClickLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
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
        $startDate = now()->subDays($days)->startOfDay();

        $trends = ClickLog::where('clicked_at', '>=', $startDate)
            ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['code' => 0, 'data' => $trends]);
    }

    public function topSites(Request $request): JsonResponse
    {
        $days = $this->getDays($request);
        $limit = $this->getLimit($request);
        $startDate = now()->subDays($days)->startOfDay();

        $topSites = ClickLog::where('clicked_at', '>=', $startDate)
            ->select('site_id', DB::raw('COUNT(*) as clicks'))
            ->groupBy('site_id')
            ->orderBy('clicks', 'desc')
            ->limit($limit)
            ->with('site:id,title,url,category_id')
            ->get();

        return response()->json(['code' => 0, 'data' => $topSites]);
    }

    public function summary(Request $request): JsonResponse
    {
        $days = $this->getDays($request);
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

        return response()->json([
            'code' => 0,
            'data' => [
                'total_clicks' => $totalClicks,
                'unique_visitors' => $uniqueIps,
                'avg_daily_clicks' => $avgDaily,
                'today_clicks' => $todayClicks,
                'click_growth' => $clickGrowth,
            ],
        ]);
    }

    public function topCategories(Request $request): JsonResponse
    {
        $days = $this->getDays($request);
        $limit = $this->getLimit($request, 50);
        $startDate = now()->subDays($days)->startOfDay();

        $topCategories = ClickLog::where('clicked_at', '>=', $startDate)
            ->join('sites', 'click_logs.site_id', '=', 'sites.id')
            ->join('categories', 'sites.category_id', '=', 'categories.id')
            ->select('categories.id', 'categories.name', 'categories.icon', DB::raw('COUNT(*) as clicks'))
            ->groupBy('categories.id', 'categories.name', 'categories.icon')
            ->orderBy('clicks', 'desc')
            ->limit($limit)
            ->get();

        return response()->json(['code' => 0, 'data' => $topCategories]);
    }

    public function hourlyDistribution(Request $request): JsonResponse
    {
        $days = $this->getDays($request);
        $startDate = now()->subDays($days)->startOfDay();

        $hours = ClickLog::where('clicked_at', '>=', $startDate)
            ->select(DB::raw('HOUR(clicked_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->keyBy('hour');

        $data = [];
        for ($i = 0; $i < 24; $i++) {
            $data[] = [
                'hour' => $i,
                'count' => $hours->get($i)?->count ?? 0,
            ];
        }

        return response()->json(['code' => 0, 'data' => $data]);
    }

    public function recentClicks(Request $request): JsonResponse
    {
        $limit = $this->getLimit($request);

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
