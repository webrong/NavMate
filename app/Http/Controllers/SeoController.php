<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    public const SITEMAP_CACHE_KEY = 'seo:sitemap';

    /**
     * Generate sitemap.xml dynamically.
     *
     * Cached — crawlers hit this repeatedly and the content only changes when
     * content changes (invalidated in ClearsDashboardCache).
     */
    public function sitemap(): Response
    {
        $xml = Cache::remember(self::SITEMAP_CACHE_KEY, 3600, function () {
            $siteUrl = config('app.url');

            $maxUpdated = Site::max('updated_at');
            $lastModified = $maxUpdated
                ? (new Carbon($maxUpdated))->toIso8601String()
                : now()->toIso8601String();

            // Only real, crawlable URLs are submitted — the SPA category views
            // are hash anchors on the homepage, which sitemap parsers ignore
            return response()
                ->view('seo.sitemap', compact('siteUrl', 'lastModified'))
                ->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Generate robots.txt dynamically
     */
    public function robots(): Response
    {
        $siteUrl = config('app.url');

        return response()
            ->view('seo.robots', compact('siteUrl'))
            ->header('Content-Type', 'text/plain');
    }
}
