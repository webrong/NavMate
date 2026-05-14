<?php

namespace App\Http\Controllers;

use App\Models\ClickLog;
use App\Models\Site;
use App\Services\UrlFetcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function fetchUrl(Request $request): JsonResponse
    {
        $request->validate(['url' => 'required|url|max:2048']);

        $result = app(UrlFetcherService::class)->fetch($request->url);

        return response()->json($result);
    }

    public function quickAdd(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url|max:2048',
            'title' => 'nullable|string|max:255',
            'favicon_url' => 'nullable|url|max:2048',
            'category_id' => 'required|exists:categories,id',
        ]);

        // Get or create visitor token
        $visitorToken = Cookie::get('visitor_token');
        if (!$visitorToken) {
            $visitorToken = Str::random(32);
        }

        $site = Site::create([
            'category_id' => $request->category_id,
            'title' => $request->title ?? parse_url($request->url, PHP_URL_HOST) ?? $request->url,
            'url' => $request->url,
            'favicon_url' => $request->favicon_url,
            'is_public' => false,
            'visitor_token' => $visitorToken,
        ]);

        return response()->json(['success' => true, 'site' => $site])
            ->withCookie(Cookie::make(
                'visitor_token', $visitorToken, 60 * 24 * 365,
                '/', null, true, true, false, 'Lax'
            )); // 1 year, secure + httpOnly + SameSite=Lax
    }

    public function click(Request $request): JsonResponse
    {
        $request->validate(['site_id' => 'required|exists:sites,id']);

        $site = Site::find($request->site_id);

        if (!$site) {
            return response()->json(['success' => false], 404);
        }

        // Deduplication: max 1 click per IP per site per hour (counter + log)
        $dedupKey = 'click:' . $request->ip() . ':' . $site->id;
        if (!Cache::has($dedupKey)) {
            $site->increment('clicks');
            Cache::put($dedupKey, true, 3600); // 1 hour window

            ClickLog::create([
                'site_id' => $site->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'clicked_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->get('q', '');

        // Limit query length to prevent abuse
        if (mb_strlen($q) > 200) {
            $q = mb_substr($q, 0, 200);
        }

        $visitorToken = Cookie::get('visitor_token');

        // Escape LIKE wildcards to prevent unintended matching
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $q);

        $sites = Site::active()
            ->where(function ($query) use ($visitorToken) {
                $query->where('is_public', true);
                if ($visitorToken) {
                    $query->orWhere('visitor_token', $visitorToken);
                }
            })
            ->whereHas('category', function ($query) {
                $query->where('is_active', true);
            })
            ->where(function ($query) use ($escaped) {
                $query->where('title', 'like', "%{$escaped}%")
                    ->orWhere('description', 'like', "%{$escaped}%")
                    ->orWhere('url', 'like', "%{$escaped}%");
            })
            ->with('category')
            ->ordered()
            ->limit(20)
            ->get();

        return response()->json($sites);
    }
}
