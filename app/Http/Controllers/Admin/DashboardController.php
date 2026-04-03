<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ClickLog;
use App\Models\Site;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'categories' => Category::count(),
            'sites' => Site::count(),
            'public_sites' => Site::where('is_public', true)->count(),
            'private_sites' => Site::where('is_public', false)->count(),
            'total_clicks' => Site::sum('clicks'),
            'today_clicks' => ClickLog::whereDate('clicked_at', today())->count(),
        ];

        $recentSites = Site::with('category')->latest()->take(10)->get();
        $topSites = Site::with('category')->orderBy('clicks', 'desc')->take(10)->get();

        return view('admin.dashboard', compact('stats', 'recentSites', 'topSites'));
    }
}
