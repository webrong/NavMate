User-agent: *
Disallow: /api/
Disallow: /admin/
Disallow: /install/
Disallow: /favorites
Disallow: /settings
Disallow: /profile
Disallow: /email/
Disallow: /_ignition

# Aggressive Chinese spiders - slow down
User-agent: 360Spider
Crawl-delay: 5

User-agent: Sogou web spider
Crawl-delay: 5

User-agent: Sogou inst spider
Crawl-delay: 5

User-agent: Bytespider
Crawl-delay: 3

Sitemap: {{ $siteUrl }}/sitemap.xml
