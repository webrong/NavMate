<!DOCTYPE html>
<html lang="zh-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=5.0">
    @php
        $siteName = $settings['site_name'] ?? config('app.name', 'NavMate');
        $siteDescription = $settings['site_description'] ?? ($siteName . ' - 现代化网址导航系统');
        $siteKeywords = $settings['site_keywords'] ?? ($siteName . ',网址导航,导航站,NavMate');
        $siteLogo = $settings['site_logo'] ?? asset('static/image/logo.png');
        $siteUrl = config('app.url');
        $currentUrl = $siteUrl . request()->getRequestUri();
    @endphp

    <title>{{ $siteName }}</title>
    <meta name="description" content="{{ $siteDescription }}" />
    <meta name="keywords" content="{{ $siteKeywords }}" />
    <meta name="theme-color" content="#f9f9f9" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="{{ $currentUrl }}" />

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ $siteLogo }}">
    <link rel="apple-touch-icon" href="{{ $siteLogo }}">

    <!-- Open Graph -->
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $siteName }}" />
    <meta property="og:description" content="{{ $siteDescription }}" />
    <meta property="og:url" content="{{ $currentUrl }}" />
    <meta property="og:image" content="{{ $siteLogo }}" />
    <meta property="og:locale" content="zh_CN" />
    <meta property="og:site_name" content="{{ $siteName }}" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="{{ $siteName }}" />
    <meta name="twitter:description" content="{{ $siteDescription }}" />
    <meta name="twitter:image" content="{{ $siteLogo }}" />

    <!-- Search Engine Verification -->
    @if(!empty($settings['baidu_verify']))
    <meta name="baidu-site-verification" content="{{ $settings['baidu_verify'] }}" />
    @endif
    @if(!empty($settings['google_verify']))
    <meta name="google-site-verification" content="{{ $settings['google_verify'] }}" />
    @endif
    @if(!empty($settings['bing_verify']))
    <meta name="msvalidate.01" content="{{ $settings['bing_verify'] }}" />
    @endif

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    @php
    $jsonLd = [
        "@context" => "https://schema.org",
        "@type" => "WebSite",
        "name" => $siteName,
        "url" => $siteUrl,
        "description" => $siteDescription,
        "potentialAction" => [
            "@type" => "SearchAction",
            "target" => [
                "@type" => "EntryPoint",
                "urlTemplate" => $siteUrl . "/?q={search_term_string}",
            ],
            "query-input" => "required name=search_term_string",
        ],
    ];
    echo json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp
    </script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="/static/css/iconfont.css">
    <link rel="stylesheet" href="/static/css/font_4814538_uewl30t0cv.css">
    @vite(['resources/css/tailwind.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
