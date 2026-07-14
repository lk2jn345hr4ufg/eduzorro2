<?php

namespace App\Console\Commands;

use App\Models\Language;
use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Creates the real countries and languages behind the WordPress-imported
 * data, constrains each region to the languages it actually supports
 * (Ukraine: Ukrainian + Russian, Kazakhstan: Russian only), deactivates the
 * starter scaffold's demo regions/languages, and links every imported
 * listing/business to its region.
 *
 * Source data only tags businesses with a country (11,321 Ukraine, 1,249
 * Kazakhstan, from the old `country` taxonomy). The five listing verticals
 * (courses, universities, etc.) were never tagged by country on the old
 * site — but the site itself is Ukraine-market content throughout (Ukrainian
 * cities, Ukrainian institutions), so listings are assigned to Ukraine by
 * default. That's a deliberate inference, not source data — flagged here
 * and in WORDPRESS-IMPORT.md.
 *
 * Demo regions/languages/industries/categories/companies (the original
 * starter scaffold's placeholder content) are deactivated (is_active =
 * false), not deleted — their data stays in the database in case you want
 * it back.
 */
class LinkWordPressRegions extends Command
{
    protected $signature = 'import:wordpress-regions';

    protected $description = 'Create Ukraine/Kazakhstan regions and Russian/Ukrainian languages, constrain each region\'s languages, deactivate demo content, and link imported data';

    public function handle(): int
    {
        $this->info('Creating languages...');
        $ru = Language::updateOrCreate(
            ['code' => 'ru'],
            ['name' => 'Russian', 'native_name' => 'Русский', 'direction' => 'ltr', 'sort_order' => 1, 'is_active' => true]
        );
        $uk = Language::updateOrCreate(
            ['code' => 'uk'],
            ['name' => 'Ukrainian', 'native_name' => 'Українська', 'direction' => 'ltr', 'sort_order' => 2, 'is_active' => true]
        );
        $this->line("  ru (#{$ru->id}), uk (#{$uk->id})");

        $this->info('Creating regions...');
        $ukraine = Region::updateOrCreate(
            ['slug' => 'ukraine'],
            [
                'code' => 'UA',
                'name' => ['ru' => 'Украина', 'uk' => 'Україна', 'en' => 'Ukraine'],
                'latitude' => 48.3794,
                'longitude' => 31.1656,
                'sort_order' => 0,
                'is_active' => true,
            ]
        );
        $kazakhstan = Region::updateOrCreate(
            ['slug' => 'kazakhstan'],
            [
                'code' => 'KZ',
                'name' => ['ru' => 'Казахстан', 'uk' => 'Казахстан', 'en' => 'Kazakhstan'],
                'latitude' => 48.0196,
                'longitude' => 66.9237,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
        $this->line("  Ukraine (#{$ukraine->id}), Kazakhstan (#{$kazakhstan->id})");

        $this->info('Setting which languages each region supports...');
        $ukraine->languages()->sync([$uk->id, $ru->id]);
        $kazakhstan->languages()->sync([$ru->id]);
        $this->line('  Ukraine: uk, ru — Kazakhstan: ru only');

        $this->info('Deactivating demo content (the starter scaffold\'s placeholder regions/languages)...');
        $demoRegionsHidden = Region::whereNotIn('slug', ['ukraine', 'kazakhstan'])->update(['is_active' => false]);
        $demoLanguagesHidden = Language::whereNotIn('code', ['ru', 'uk'])->update(['is_active' => false]);
        $this->line("  {$demoRegionsHidden} other region(s) and {$demoLanguagesHidden} other language(s) deactivated (not deleted).");

        // The starter scaffold's demo Industries/Categories/Companies (language-learning,
        // test-prep, etc.) were only ever translated into English/Spanish. With Ukraine
        // and Kazakhstan now the only active regions, that section would otherwise show
        // untranslated English category names on every region page — deactivate it too.
        $demoIndustriesHidden = DB::table('industries')->update(['is_active' => false]);
        $demoCategoriesHidden = DB::table('categories')->update(['is_active' => false]);
        $demoCompaniesHidden = DB::table('companies')->update(['is_active' => false]);
        $this->line("  {$demoIndustriesHidden} demo industries, {$demoCategoriesHidden} demo categories, {$demoCompaniesHidden} demo companies deactivated.");

        $this->info('Linking businesses to regions (from their country taxonomy term)...');
        $linked = 0;
        foreach (['ukraine' => $ukraine->id, 'kazakhstan' => $kazakhstan->id] as $slug => $regionId) {
            $count = DB::table('businesses')
                ->whereIn('id', function ($q) use ($slug) {
                    $q->select('business_taxonomy_term.business_id')
                        ->from('business_taxonomy_term')
                        ->join('taxonomy_terms', 'taxonomy_terms.id', '=', 'business_taxonomy_term.taxonomy_term_id')
                        ->where('taxonomy_terms.taxonomy', 'country')
                        ->where('taxonomy_terms.slug', $slug);
                })
                ->update(['region_id' => $regionId]);
            $this->line("  {$slug}: {$count} businesses");
            $linked += $count;
        }

        $unlinked = DB::table('businesses')->whereNull('region_id')->count();
        if ($unlinked > 0) {
            $this->line("  {$unlinked} businesses had no country term and remain unlinked.");
        }

        $this->info('Linking listings to Ukraine (default — see command docblock)...');
        $listingsLinked = DB::table('listings')->update(['region_id' => $ukraine->id]);
        $this->line("  {$listingsLinked} listings linked.");

        $this->info('Done.');

        return self::SUCCESS;
    }
}
