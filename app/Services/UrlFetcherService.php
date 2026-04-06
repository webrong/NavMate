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

        // SSRF protection: resolve DNS once and pin the IP for the request
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return ['title' => null, 'favicon_url' => null];
        }

        // Block known internal hostnames
        $blockedHosts = ['localhost', 'metadata.google.internal', 'metadata'];
        if (in_array(strtolower($host), $blockedHosts, true)) {
            Log::warning('UrlFetcher blocked internal hostname', ['url' => $url]);
            return ['title' => null, 'favicon_url' => null];
        }

        // Resolve DNS and check IP ONCE — then pin for the actual request
        $resolvedIp = gethostbyname($host);
        if ($resolvedIp === $host) {
            // DNS resolution failed
            return ['title' => null, 'favicon_url' => null];
        }

        if (self::isInternalIp($resolvedIp)) {
            Log::warning('UrlFetcher blocked internal IP', ['url' => $url, 'ip' => $resolvedIp]);
            return ['title' => null, 'favicon_url' => null];
        }

        try {
            $port = parse_url($url, PHP_URL_PORT) ?? (parse_url($url, PHP_URL_SCHEME) === 'https' ? 443 : 80);

            // Pin the resolved IP via cURL RESOLVE option to prevent DNS rebinding
            $response = Http::timeout(10)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->withOptions([
                    'curl' => [
                        CURLOPT_RESOLVE => ["{$host}:{$port}:{$resolvedIp}"],
                    ],
                ])
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

    /**
     * Check if an IP address is in a private/reserved range.
     * Public static so it can be reused (e.g., for SMTP host validation).
     */
    public static function isInternalIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
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

        // Fallback: try site's own /favicon.ico
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        if ($host) {
            return $scheme . '://' . $host . '/favicon.ico';
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
