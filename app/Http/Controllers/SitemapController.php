<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Language;
use App\Models\Region;

class SitemapController extends Controller
{
    /**
     * A single sitemap with hreflang alternates on every URL.
     *
     * For a large catalogue, split this into a sitemap index with paginated
     * child sitemaps (Google caps a sitemap at 50k URLs / 50MB).
     */
    public function index()
    {
        $languages  = Language::active()->ordered()->get();
        $regions    = Region::active()->ordered()->get();
        $industries = Industry::active()->ordered()->get();
        $categories = Category::active()->with('industry')->get();

        $urls = [];

        // Region + language homes
        foreach ($regions as $region) {
            $urls[] = $this->entry('region.home', [$region], $languages, $region);
        }

        // Industry + category pages, per region
        foreach ($regions as $region) {
            foreach ($industries as $industry) {
                $urls[] = $this->entry('industry.show', [$region, $industry], $languages, $region, $industry);
            }
            foreach ($categories as $category) {
                $urls[] = $this->entry(
                    'category.show',
                    [$region, $category->industry, $category],
                    $languages,
                    $region,
                    $category->industry,
                    $category
                );
            }
        }

        // Companies (only in the regions they belong to)
        Company::active()->with('regions', 'category.industry')->chunk(500, function ($companies) use ($languages, &$urls) {
            foreach ($companies as $company) {
                foreach ($company->regions as $region) {
                    if (! $region->is_active) {
                        continue;
                    }
                    $urls[] = $this->entry(
                        'company.show',
                        [$region, $company],
                        $languages,
                        $region,
                        null,
                        null,
                        $company
                    );
                }
            }
        });

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Build one <url> entry (default language loc + alternates for all languages).
     * The extra model args are only used to keep parameter arrays intact per language.
     */
    private function entry(string $routeName, array $baseParams, $languages, ...$context): array
    {
        $default = $languages->first();

        $loc = route($routeName, $this->withLanguage($baseParams, $default));

        $alternates = [];
        foreach ($languages as $lang) {
            $alternates[$lang->code] = route($routeName, $this->withLanguage($baseParams, $lang));
        }

        return ['loc' => $loc, 'alternates' => $alternates];
    }

    /** Insert the language param at position 1 (region is always position 0). */
    private function withLanguage(array $params, Language $language): array
    {
        $out = $params;
        array_splice($out, 1, 0, [$language]);
        return $out;
    }
}
