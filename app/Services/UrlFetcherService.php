<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UrlFetcherService
{
    public function fetch(string $url): array
    {
        try {
            $html = $this->fetchHtml($url);
        } catch (\Throwable $e) {
            Log::warning('UrlFetcher failed', ['url' => $url, 'error' => $e->getMessage()]);

            return ['title' => null, 'favicon_url' => null];
        }

        if ($html === null) {
            return ['title' => null, 'favicon_url' => null];
        }

        return [
            'title' => $this->extractTitle($html),
            'favicon_url' => $this->extractFavicon($html, $url),
        ];
    }

    /**
     * Fetch the page body, following redirects manually so that every hop
     * goes through the full SSRF validation (scheme, hostname, resolved IP).
     */
    private function fetchHtml(string $url): ?string
    {
        $current = $url;

        for ($hop = 0; $hop < 5; $hop++) {
            $parsed = parse_url($current);
            $scheme = strtolower($parsed['scheme'] ?? '');

            if (! in_array($scheme, ['http', 'https'], true)) {
                return null;
            }

            $host = $parsed['host'] ?? '';
            if ($host === '') {
                return null;
            }

            // Block known internal hostnames
            $blockedHosts = ['localhost', 'metadata.google.internal', 'metadata'];
            if (in_array(strtolower($host), $blockedHosts, true)) {
                Log::warning('UrlFetcher blocked internal hostname', ['url' => $current]);

                return null;
            }

            // IP literals bypass gethostbyname (which would fail on them)
            if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                $resolvedIp = $host;
            } else {
                // Resolve DNS and check the IP, then pin it for the request
                $resolvedIp = gethostbyname($host);
                if ($resolvedIp === $host) {
                    // DNS resolution failed
                    return null;
                }
            }

            if (self::isInternalIp($resolvedIp)) {
                Log::warning('UrlFetcher blocked internal IP', ['url' => $current, 'ip' => $resolvedIp]);

                return null;
            }

            // Pin the resolved IP via cURL RESOLVE option to prevent DNS rebinding.
            // Redirects must never bypass the checks above, so automatic
            // following stays disabled and each Location is re-validated.
            $port = $parsed['port'] ?? ($scheme === 'https' ? 443 : 80);
            $response = Http::timeout(10)
                ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
                ->withOptions([
                    'allow_redirects' => false,
                    'curl' => [
                        CURLOPT_RESOLVE => ["{$host}:{$port}:{$resolvedIp}"],
                    ],
                ])
                ->get($current);

            if ($response->redirect()) {
                $location = $response->header('Location');
                if ($location === '') {
                    return null;
                }
                $current = $this->resolveUrl($current, $location);

                continue;
            }

            return $response->successful() ? $response->body() : null;
        }

        return null; // Redirect limit exceeded
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
            // Truncated to fit varchar(255) columns in strict SQL mode
            $title = mb_substr($title, 0, 255);

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
            return $scheme.'://'.$host.'/favicon.ico';
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
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        if (str_starts_with($relative, '//')) {
            return $scheme.':'.$relative;
        }

        if (str_starts_with($relative, '/')) {
            return $scheme.'://'.$host.$port.$relative;
        }

        // Relative path
        $path = $parsed['path'] ?? '/';
        $dir = dirname($path);
        if ($dir === '.') {
            $dir = '/';
        }

        return $scheme.'://'.$host.$port.$dir.'/'.$relative;
    }
}
