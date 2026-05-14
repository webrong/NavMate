<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>

<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $siteUrl }}/</loc>
        <lastmod>{{ $lastModified }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <url>
        <loc>{{ $siteUrl }}/about</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <url>
        <loc>{{ $siteUrl }}/terms</loc>
        <changefreq>monthly</changefreq>
        <priority>0.3</priority>
    </url>

    @foreach($categories as $category)
    <url>
        <loc>{{ $siteUrl }}/#category-{{ $category->id }}</loc>
        <lastmod>{{ $category->updated_at?->toIso8601String() ?? $category->created_at?->toIso8601String() ?? $lastModified }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
</urlset>
