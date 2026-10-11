<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\File;

class SitemapService
{
    /**
     * Generate sitemap XML content.
     */
    public function generateXml(): string
    {
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');

        $urls = [];

        // 1. Static storefront pages
        $staticRoutes = [
            ['loc' => "{$baseUrl}/", 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => now()->toIso8601String()],
            ['loc' => "{$baseUrl}/shop", 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => now()->toIso8601String()],
            ['loc' => "{$baseUrl}/gifts", 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
            ['loc' => "{$baseUrl}/brands", 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => now()->toIso8601String()],
            ['loc' => "{$baseUrl}/about", 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
            ['loc' => "{$baseUrl}/contact", 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => now()->toIso8601String()],
        ];

        foreach ($staticRoutes as $r) {
            $urls[] = $r;
        }

        // 2. Published CMS pages
        $pages = Page::published()->get();
        foreach ($pages as $p) {
            $path = in_array($p->slug, ['about', 'delivery-returns', 'privacy', 'terms'])
                ? "/{$p->slug}"
                : "/p/{$p->slug}";

            $urls[] = [
                'loc' => "{$baseUrl}{$path}",
                'priority' => '0.5',
                'changefreq' => 'monthly',
                'lastmod' => $p->updated_at->toIso8601String(),
            ];
        }

        // 3. Categories
        $categories = Category::all();
        foreach ($categories as $c) {
            $urls[] = [
                'loc' => "{$baseUrl}/category/{$c->slug}",
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $c->updated_at->toIso8601String(),
            ];
        }

        // 4. Brands
        $brands = Brand::all();
        foreach ($brands as $b) {
            $urls[] = [
                'loc' => "{$baseUrl}/brand/{$b->slug}",
                'priority' => '0.7',
                'changefreq' => 'weekly',
                'lastmod' => $b->updated_at->toIso8601String(),
            ];
        }

        // 5. Products
        $products = Product::active()->get();
        foreach ($products as $pr) {
            $urls[] = [
                'loc' => "{$baseUrl}/product/{$pr->slug}",
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $pr->updated_at->toIso8601String(),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($u['loc'], ENT_XML1, 'UTF-8') . "</loc>\n";
            $xml .= "    <lastmod>{$u['lastmod']}</lastmod>\n";
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$u['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate sitemap and save to public/sitemap.xml.
     */
    public function generateAndSave(): string
    {
        $xml = $this->generateXml();
        $path = public_path('sitemap.xml');
        File::put($path, $xml);
        return $path;
    }
}
