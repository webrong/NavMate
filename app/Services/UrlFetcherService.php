<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrlFetcherService
{
    public function fetch(string $url): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['title' => null, 'favicon_url' => null];
        }

        try {
            $response = Http::timeout(10)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->get($url);

            if (!$response->successful()) {
                return ['title' => null, 'favicon_url' => null];
            }

            $html = $response->body();
            $title = $this->extractTitle($html);
            $faviconUrl = $this->extractFavicon($html, $url);

            return [
                'title' => $title,
                'favicon_url' => $faviconUrl,
            ];
        } catch (\Throwable $e) {
            Log::warning('UrlFetcher failed', ['url' => $url, 'error' => $e->getMessage()]);
            return ['title' => null, 'favicon_url' => null];
        }
    }

    private function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $title = trim($matches[1]);
            // Decode HTML entities
            $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return $title !== '' ? $title : null;
        }
        return null;
    }

    private function extractFavicon(string $html, string $baseUrl): ?string
    {
        // Try to find <link rel="icon"> or <link rel="shortcut icon">
        if (preg_match('/<link[^>]+rel=["\'](?:shortcut\s+)?icon["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return $this->resolveUrl($baseUrl, $matches[1]);
        }
        if (preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\'](?:shortcut\s+)?icon["\']/i', $html, $matches)) {
            return $this->resolveUrl($baseUrl, $matches[1]);
        }

        // Fallback: use Google favicon API (works in China via gstatic.cn)
        $parsed = parse_url($baseUrl);
        $host = $parsed['host'] ?? '';
        if ($host) {
            return 'https://t2.gstatic.cn/faviconV2?client=SOCIAL&type=FAVICON&fallback_opts=TYPE,SIZE,URL&size=128&url=https://' . $host;
        }

        return null;
    }

    private function resolveUrl(string $base, string $relative): string
    {
        // Already absolute
        if (preg_match('/^https?:\/\//i', $relative)) {
            return $relative;
        }

        $parsed = parse_url($base);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';

        if (str_starts_with($relative, '//')) {
            return $scheme . ':' . $relative;
        }

        if (str_starts_with($relative, '/')) {
            return $scheme . '://' . $host . $port . $relative;
        }

        // Relative path
        $path = $parsed['path'] ?? '/';
        $dir = dirname($path);
        if ($dir === '.') {
            $dir = '/';
        }

        return $scheme . '://' . $host . $port . $dir . '/' . $relative;
    }
}
