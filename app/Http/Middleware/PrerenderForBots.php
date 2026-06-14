<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\CategoryTreeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrerenderForBots
{
    /**
     * Known search engine bot user-agent patterns
     */
    protected const BOT_PATTERNS = [
        'Googlebot',
        'bingbot',
        'Baiduspider',
        'Sogou web spider',
        'Sogou inst spider',
        '360Spider',
        'Bytespider',
        'YisouSpider',
        'ia_archiver',
        'AhrefsBot',
        'MJ12bot',
        'SemrushBot',
        'facebookexternalhit',
        'Twitterbot',
        'LinkedInBot',
        'Slackbot',
        'Discordbot',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $ua = $request->userAgent() ?? '';

        // Only intercept GET requests from bots for the frontend pages
        if ($request->isMethod('GET') && $this->isBot($ua) && $this->shouldPrerender($request)) {
            return $this->renderForBot($request);
        }

        return $next($request);
    }

    protected function isBot(string $ua): bool
    {
        foreach (self::BOT_PATTERNS as $pattern) {
            if (stripos($ua, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function shouldPrerender(Request $request): bool
    {
        $path = $request->path();

        // Skip API, admin, install routes
        if (str_starts_with($path, 'api/') ||
            str_starts_with($path, 'admin') ||
            str_starts_with($path, 'install') ||
            str_starts_with($path, '_ignition') ||
            $path === 'sitemap.xml' ||
            $path === 'robots.txt') {
            return false;
        }

        return true;
    }

    protected function renderForBot(Request $request): Response
    {
        try {
            $treeService = app(CategoryTreeService::class);
            $categories = $treeService->getPublicTree();
        } catch (\Throwable) {
            $categories = collect();
        }

        $allSettings = collect();

        try {
            $allSettings = Setting::allCached();
        } catch (\Throwable) {
            // Database not available during installation
        }

        $siteName = $allSettings->get('site_name') ?: config('app.name', '导航');
        $siteDescription = $allSettings->get('site_description') ?: ($siteName.' - 现代化网址导航系统');
        $siteKeywords = $allSettings->get('site_keywords') ?: ($siteName.',网址导航,导航站,NavMate');
        $siteLogo = $allSettings->get('site_logo') ?: asset('static/image/logo.svg');
        $siteUrl = config('app.url');
        $footerText = $allSettings->get('footer_text') ?: '';
        $icpNumber = $allSettings->get('icp_number') ?: '';

        return response()->view('seo.prerender', compact(
            'categories',
            'allSettings',
            'siteName',
            'siteDescription',
            'siteKeywords',
            'siteLogo',
            'siteUrl',
            'footerText',
            'icpNumber',
        ));
    }
}
