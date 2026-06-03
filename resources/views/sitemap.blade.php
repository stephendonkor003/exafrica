{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($sections as $key => $section)
    <url>
        <loc>{{ $key === config('seo.default_section') ? url('/') : url('/?section='.$key) }}</loc>
        <lastmod>{{ $lastmod }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>{{ $section['priority'] }}</priority>
    </url>
@endforeach
</urlset>
