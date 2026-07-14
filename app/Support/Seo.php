<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Language;
use Illuminate\Support\Facades\Route;

/**
 * SEO helpers: hreflang alternates and JSON-LD structured data.
 * Kept static and dependency-free so views/controllers can call them directly.
 */
class Seo
{
    /**
     * Build hreflang alternates for the CURRENT route by swapping the {language}
     * parameter for every active language, keeping all other params identical.
     *
     * @return array<int, array{hreflang:string, href:string}>
     */
    public static function hreflangAlternates(): array
    {
        $route = request()->route();

        if (! $route || ! array_key_exists('language', $route->parameters())) {
            return [];
        }

        $name   = $route->getName();
        $params = $route->parameters();
        $out    = [];

        // Only offer languages the current region actually supports — e.g.
        // Kazakhstan is Russian-only, so it shouldn't advertise a Ukrainian
        // alternate that would just 404.
        $region = $params['region'] ?? null;
        $languages = $region instanceof \App\Models\Region
            ? $region->languages()->active()->ordered()->get()
            : Language::active()->ordered()->get();

        foreach ($languages as $lang) {
            $p             = $params;
            $p['language'] = $lang; // getRouteKeyName() = 'code'
            try {
                $out[] = ['hreflang' => $lang->code, 'href' => route($name, $p)];
            } catch (\Throwable $e) {
                // route not resolvable for this language; skip silently
            }
        }

        return $out;
    }

    /** BreadcrumbList JSON-LD from [['label' => ..., 'url' => ...], ...]. */
    public static function breadcrumbList(array $items): array
    {
        $elements = [];
        $position = 1;

        foreach ($items as $item) {
            $entry = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $item['label'],
            ];
            if (! empty($item['url'])) {
                $entry['item'] = $item['url'];
            }
            $elements[] = $entry;
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /** LocalBusiness / Organization JSON-LD for a company profile. */
    public static function localBusiness(Company $company, float $rating = 0.0, int $reviewCount = 0): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type'    => $company->type === 'digital' ? 'Organization' : 'LocalBusiness',
            'name'     => $company->name,
            'url'      => url()->current(),
        ];

        if ($desc = $company->translate('description')) {
            $data['description'] = strip_tags($desc);
        }
        if ($company->website) {
            $data['sameAs'] = [$company->website];
        }
        if ($company->phone) {
            $data['telephone'] = $company->phone;
        }
        if ($company->email) {
            $data['email'] = $company->email;
        }
        if ($company->address) {
            $data['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $company->address];
        }
        if ($company->latitude && $company->longitude) {
            $data['geo'] = [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $company->latitude,
                'longitude' => $company->longitude,
            ];
        }
        if ($reviewCount > 0) {
            $data['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => round($rating, 1),
                'reviewCount' => $reviewCount,
                'bestRating'  => 5,
                'worstRating' => 1,
            ];
        }

        return $data;
    }

    /** ItemList JSON-LD for a category listing page. */
    public static function itemList(array $urls): array
    {
        $elements = [];
        $position = 1;

        foreach ($urls as $url) {
            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'url'      => $url,
            ];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'ItemList',
            'itemListElement' => $elements,
        ];
    }
}
