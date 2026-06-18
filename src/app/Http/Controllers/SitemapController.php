<?php

namespace App\Http\Controllers;

use App\Modules\Products\Models\Product;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\Response;

/**
 * P9: XML sitemap of public, indexable URLs — static pages + active listings.
 * Soft-deleted/inactive listings are excluded (only `active` scope).
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        // Static + catalogue landing pages.
        foreach (['home', 'products.index', 'vehicles.index', 'pages.how-fbs-works',
                  'pages.rfq-guide', 'pages.fees', 'pages.cod-policy', 'pages.terms', 'pages.privacy'] as $name) {
            $urls[] = ['loc' => route($name), 'changefreq' => 'weekly'];
        }

        // Active product + vehicle detail pages.
        Product::active()->select('id', 'updated_at')->orderByDesc('updated_at')->limit(5000)
            ->each(function (Product $p) use (&$urls) {
                $urls[] = ['loc' => route('products.show', $p), 'lastmod' => $p->updated_at?->toAtomString()];
            });

        Vehicle::active()->select('id', 'updated_at')->orderByDesc('updated_at')->limit(5000)
            ->each(function (Vehicle $v) use (&$urls) {
                $urls[] = ['loc' => route('vehicles.show', $v), 'lastmod' => $v->updated_at?->toAtomString()];
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
                . (isset($u['lastmod']) ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
                . (isset($u['changefreq']) ? '<changefreq>' . $u['changefreq'] . '</changefreq>' : '')
                . '</url>' . "\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
