<!DOCTYPE html>
<html lang="zh-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteName }}</title>
    <meta name="description" content="{{ $siteDescription }}" />
    <meta name="keywords" content="{{ $siteKeywords }}" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="{{ $siteUrl }}/" />

    <link rel="shortcut icon" href="{{ $siteLogo }}">
    <link rel="apple-touch-icon" href="{{ $siteLogo }}">

    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $siteName }}" />
    <meta property="og:description" content="{{ $siteDescription }}" />
    <meta property="og:url" content="{{ $siteUrl }}/" />
    <meta property="og:image" content="{{ $siteLogo }}" />
    <meta property="og:locale" content="zh_CN" />
    <meta property="og:site_name" content="{{ $siteName }}" />

    <meta name="twitter:card" content="summary" />
    <meta name="twitter:title" content="{{ $siteName }}" />
    <meta name="twitter:description" content="{{ $siteDescription }}" />
    <meta name="twitter:image" content="{{ $siteLogo }}" />

    @if($allSettings ?? null)
        @php $allSettings = $allSettings instanceof \Illuminate\Support\Collection ? $allSettings : collect() @endphp
        @if($allSettings->get('baidu_verify'))
    <meta name="baidu-site-verification" content="{{ $allSettings->get('baidu_verify') }}" />
        @endif
        @if($allSettings->get('google_verify'))
    <meta name="google-site-verification" content="{{ $allSettings->get('google_verify') }}" />
        @endif
        @if($allSettings->get('bing_verify'))
    <meta name="msvalidate.01" content="{{ $allSettings->get('bing_verify') }}" />
        @endif
    @endif

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

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #333; background: #f9f9f9; line-height: 1.6; }
        a { color: #1890ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .header { background: #fff; border-bottom: 1px solid #e8e8e8; padding: 12px 24px; display: flex; align-items: center; gap: 16px; }
        .header h1 { font-size: 20px; font-weight: 600; }
        .header .desc { font-size: 13px; color: #888; }
        .main { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .category { margin-bottom: 24px; background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .category h2 { font-size: 16px; font-weight: 600; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #f0f0f0; }
        .sites { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
        .site { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 6px; border: 1px solid #f0f0f0; transition: border-color 0.2s; }
        .site:hover { border-color: #1890ff; text-decoration: none; }
        .site img { width: 20px; height: 20px; border-radius: 4px; flex-shrink: 0; }
        .site .title { font-size: 13px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .child-categories { margin-top: 12px; padding-left: 16px; }
        .child-categories h3 { font-size: 14px; font-weight: 500; margin-bottom: 8px; color: #555; }
        .footer { text-align: center; padding: 24px; color: #999; font-size: 12px; border-top: 1px solid #e8e8e8; margin-top: 24px; }
    </style>
</head>
<body>
    <header class="header">
        @if($siteLogo)
        <img src="{{ $siteLogo }}" alt="{{ $siteName }}" width="32" height="32" style="border-radius: 6px;">
        @endif
        <div>
            <h1>{{ $siteName }}</h1>
            <div class="desc">{{ $siteDescription }}</div>
        </div>
    </header>

    <main class="main">
        @foreach($categories as $category)
        <section class="category">
            <h2>{{ $category->name }}</h2>
            @if($category->sites && $category->sites->isNotEmpty())
            <div class="sites">
                @foreach($category->sites as $site)
                <a href="{{ $site->url }}" class="site" target="_blank" rel="noopener noreferrer">
                    @if($site->favicon_url)
                    <img src="{{ $site->favicon_url }}" alt="" loading="lazy" width="20" height="20">
                    @endif
                    <span class="title">{{ $site->title }}</span>
                </a>
                @endforeach
            </div>
            @endif

            @if(isset($category->children) && $category->children->isNotEmpty())
            <div class="child-categories">
                @foreach($category->children as $child)
                <div style="margin-bottom: 16px;">
                    <h3>{{ $child->name }}</h3>
                    @if($child->sites && $child->sites->isNotEmpty())
                    <div class="sites">
                        @foreach($child->sites as $site)
                        <a href="{{ $site->url }}" class="site" target="_blank" rel="noopener noreferrer">
                            @if($site->favicon_url)
                            <img src="{{ $site->favicon_url }}" alt="" loading="lazy" width="20" height="20">
                            @endif
                            <span class="title">{{ $site->title }}</span>
                        </a>
                        @endforeach
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </section>
        @endforeach
    </main>

    <footer class="footer">
        @if($footerText)
        <p>{{ $footerText }}</p>
        @endif
        @if($icpNumber)
        <p><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer">{{ $icpNumber }}</a></p>
        @endif
    </footer>
</body>
</html>
