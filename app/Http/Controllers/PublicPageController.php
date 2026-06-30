<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicPageController extends Controller
{
    public function home(Request $request)
    {
        $sections = config('seo.sections');
        $section = $request->query('section', config('seo.default_section'));
        $section = array_key_exists($section, $sections) ? $section : config('seo.default_section');

        return view('welcome', [
            'activeSection' => $section,
            'seo' => $this->seoPayload($section),
            'publicCategories' => Category::where('is_active', true)
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'icon', 'max_nominees', 'position']),
        ]);
    }

    public function sitemap(): Response
    {
        return response()
            ->view('sitemap', [
                'sections' => config('seo.sections'),
                'lastmod' => now()->toDateString(),
            ])
            ->header('Content-Type', 'application/xml');
    }

    public function robots(): Response
    {
        return response(implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /api/',
            'Disallow: /back-office/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ]))->header('Content-Type', 'text/plain');
    }

    private function seoPayload(string $section): array
    {
        $sections = config('seo.sections');
        $meta = $sections[$section] ?? $sections[config('seo.default_section')];
        $url = $section === config('seo.default_section')
            ? url('/')
            : url('/?section='.$section);

        return [
            'section' => $section,
            'title' => $meta['title'],
            'description' => $meta['description'],
            'keywords' => implode(', ', config('seo.keywords', [])),
            'url' => $url,
            'site_name' => config('seo.site_name'),
            'brand' => config('seo.brand'),
            'image' => asset(config('seo.logos.share_card')),
            'agenda_logo' => asset(config('seo.logos.agenda_2063')),
            'au_logo' => asset(config('seo.logos.african_union')),
        ];
    }
}
