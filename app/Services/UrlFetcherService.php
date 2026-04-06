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

        // SSRF protection: block private/internal IP ranges
        if ($this->isInternalUrl($url)) {
            Log::warning('UrlFetcher blocked internal URL', ['url' => $url]);
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

        // Fallback: try site's own /favicon.ico
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        if ($host) {
            return $scheme . '://' . $host . '/favicon.ico';
        }

        return null;
    }

    private function isInternalUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return true;
        }

        // Block hostnames that resolve to internal IPs
        $blockedHosts = ['localhost', 'metadata.google.internal', 'metadata'];
        if (in_array(strtolower($host), $blockedHosts, true)) {
            return true;
        }

        // Resolve domain to IP and check if private
        $ip = gethostbyname($host);
        if ($ip === $host) {
            // Could not resolve — allow through, let HTTP client handle it
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
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
