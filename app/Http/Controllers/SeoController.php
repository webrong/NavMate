<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Site;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Generate sitemap.xml dynamically
     */
    public function sitemap(): Response
    {
        $categories = Category::active()
            ->ordered()
            ->with(['children' => function ($query) {
                $query->active();
            }])
            ->root()
            ->get();

        $siteUrl = config('app.url');

        $lastModified = Site::max('updated_at')
            ? Site::max('updated_at')->toIso8601String()
            : now()->toIso8601String();

        return response()
            ->view('seo.sitemap', compact('categories', 'siteUrl', 'lastModified'))
            ->header('Content-Type', 'application/xml');
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
