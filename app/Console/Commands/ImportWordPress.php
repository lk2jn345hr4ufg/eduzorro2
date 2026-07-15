<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Imports listings, businesses, reviews, and taxonomy terms from the
 * transformed WordPress CSV export (see database/import/*.csv).
 *
 * The heavy lifting — parsing the 142MB WXR export, unflattening ACF's
 * repeater/flexible-content field naming, resolving attachment IDs to
 * URLs — was already done once to produce those CSVs. This command's job
 * is just to load them: chunked upserts, no per-row queries, safe to
 * re-run (use --fresh to wipe and reimport from scratch).
 */
class ImportWordPress extends Command
{
    protected $signature = 'import:wordpress {--path=} {--fresh : Truncate all imported tables first} {--chunk=500}';

    protected $description = 'Import listings, businesses, reviews, and taxonomy terms from the WordPress CSV export';

    protected int $chunkSize = 500;

    public function handle(): int
    {
        $path = rtrim($this->option('path') ?: database_path('import'), '/');
        $this->chunkSize = (int) $this->option('chunk');

        if (! is_dir($path)) {
            $this->error("Import directory not found: {$path}");

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Truncating imported tables...');
            foreach ([
                'business_taxonomy_term', 'businesses',
                'listing_reviews', 'listing_pros_cons', 'listing_prices',
                'listing_addresses', 'listing_taxonomy_term', 'listings',
                'taxonomy_terms',
            ] as $table) {
                DB::table($table)->delete();
            }
        }

        $this->info('Importing taxonomy terms...');
        $this->importTaxonomyTerms("{$path}/taxonomy_terms.csv");
        $termMap = $this->buildTermMap();
        $this->line('  ' . count($termMap) . ' terms in dictionary.');

        $this->info('Importing listings...');
        $this->importListings("{$path}/listings.csv");
        $listingMap = DB::table('listings')->pluck('id', 'wp_post_id')->toArray();
        $this->line('  ' . count($listingMap) . ' listings.');

        $this->info('Importing listing addresses...');
        $this->importListingChildRows("{$path}/listing_addresses.csv", 'listing_addresses', $listingMap, function (array $row) {
            return ['address' => $row['address']];
        });

        $this->info('Importing listing prices...');
        $this->importListingChildRows("{$path}/listing_prices.csv", 'listing_prices', $listingMap, function (array $row) {
            return ['name' => $row['name'], 'price' => $row['price'], 'lessons_count' => $row['lessons_count']];
        });

        $this->info('Importing listing pros/cons...');
        $this->importListingChildRows("{$path}/listing_pros_cons.csv", 'listing_pros_cons', $listingMap, function (array $row) {
            return ['kind' => $row['kind'], 'text' => $row['text']];
        });

        $this->info('Importing listing reviews...');
        $this->importListingReviews("{$path}/listing_reviews.csv", $listingMap);

        $this->info('Importing listing categories...');
        $this->importListingCategories("{$path}/listing_categories.csv", $listingMap, $termMap);

        $this->info('Importing businesses...');
        $this->importBusinesses("{$path}/businesses.csv");
        $businessMap = DB::table('businesses')->pluck('id', 'wp_post_id')->toArray();
        $this->line('  ' . count($businessMap) . ' businesses.');

        $this->info('Importing business categories...');
        $this->importBusinessCategories("{$path}/business_categories.csv", $businessMap, $termMap);

        $this->info('Done.');

        return self::SUCCESS;
    }

    /** Read a CSV in chunks, calling $callback with each chunk of associative-array rows. */
    protected function eachChunk(string $file, callable $callback): void
    {
        if (! is_file($file)) {
            $this->warn("  Skipping missing file: {$file}");

            return;
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle, null, ',', '"', '');
        $chunk = [];

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if (count($row) !== count($header)) {
                continue; // defensively skip any malformed line
            }
            $chunk[] = array_combine($header, $row);
            if (count($chunk) >= $this->chunkSize) {
                $callback($chunk);
                $chunk = [];
            }
        }
        if ($chunk) {
            $callback($chunk);
        }
        fclose($handle);
    }

    protected function importTaxonomyTerms(string $file): void
    {
        $now = now();
        $this->eachChunk($file, function (array $rows) use ($now) {
            $values = array_map(fn ($r) => [
                'taxonomy' => $r['taxonomy'],
                'slug' => $r['slug'],
                'name' => $r['name'],
                'parent_slug' => $r['parent_slug'] ?: null,
                'created_at' => $now,
                'updated_at' => $now,
            ], $rows);

            DB::table('taxonomy_terms')->upsert(
                $values,
                ['taxonomy', 'slug'],
                ['name', 'parent_slug', 'updated_at']
            );
        });
    }

    protected function buildTermMap(): array
    {
        $map = [];
        foreach (DB::table('taxonomy_terms')->select('id', 'taxonomy', 'slug')->cursor() as $term) {
            $map["{$term->taxonomy}|{$term->slug}"] = $term->id;
        }

        return $map;
    }

    protected function importListings(string $file): void
    {
        $now = now();
        $this->eachChunk($file, function (array $rows) use ($now) {
            $values = array_map(fn ($r) => [
                'wp_post_id' => (int) $r['wp_post_id'],
                'vertical' => $r['vertical'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'description_title' => $r['description_title'] ?: null,
                'description' => $r['description'] ?: null,
                'specialization' => $r['specialization'] ?: null,
                'editorial_rating' => is_numeric($r['editorial_rating']) ? $r['editorial_rating'] : null,
                'contacts_text' => $r['contacts_text'] ?: null,
                'website' => $r['website'] ?: null,
                'logo_url' => $r['logo_url'] ?: null,
                'year_of_founded' => $r['year_of_founded'] ?: null,
                'details_description' => $r['details_description'] ?: null,
                'old_link' => $r['old_link'] ?: null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], $rows);

            DB::table('listings')->upsert(
                $values,
                ['wp_post_id'],
                ['vertical', 'name', 'slug', 'description_title', 'description', 'specialization',
                    'editorial_rating', 'contacts_text', 'website', 'logo_url', 'year_of_founded',
                    'details_description', 'old_link', 'updated_at']
            );
        });
    }

    protected function importListingChildRows(string $file, string $table, array $listingMap, callable $fields): void
    {
        $now = now();
        $this->eachChunk($file, function (array $rows) use ($table, $listingMap, $fields, $now) {
            $values = [];
            foreach ($rows as $r) {
                $listingId = $listingMap[(int) $r['wp_post_id']] ?? null;
                if (! $listingId) {
                    continue;
                }
                $values[] = array_merge(
                    ['listing_id' => $listingId, 'created_at' => $now, 'updated_at' => $now],
                    $fields($r)
                );
            }
            if ($values) {
                DB::table($table)->insert($values);
            }
        });
    }

    protected function importListingReviews(string $file, array $listingMap): void
    {
        $this->eachChunk($file, function (array $rows) use ($listingMap) {
            $values = [];
            foreach ($rows as $r) {
                $listingId = $listingMap[(int) $r['wp_post_id']] ?? null;
                if (! $listingId) {
                    continue;
                }
                $createdAt = ($r['created_at'] && strtotime($r['created_at'])) ? $r['created_at'] : now();
                $values[] = [
                    'listing_id' => $listingId,
                    'author_name' => $r['author_name'] ?: 'Anonymous',
                    'author_email' => $r['author_email'] ?: null,
                    'rating' => (int) $r['rating'],
                    'body' => $r['body'],
                    'is_approved' => true,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
            if ($values) {
                DB::table('listing_reviews')->insert($values);
            }
        });
    }

    protected function importListingCategories(string $file, array $listingMap, array $termMap): void
    {
        $now = now();
        $this->eachChunk($file, function (array $rows) use ($listingMap, $termMap, $now) {
            $values = [];
            foreach ($rows as $r) {
                $listingId = $listingMap[(int) $r['wp_post_id']] ?? null;
                $termId = $termMap["{$r['taxonomy']}|{$r['term_slug']}"] ?? null;
                if (! $listingId || ! $termId) {
                    continue;
                }
                $values[] = [
                    'listing_id' => $listingId,
                    'taxonomy_term_id' => $termId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($values) {
                DB::table('listing_taxonomy_term')->upsert($values, ['listing_id', 'taxonomy_term_id'], ['updated_at']);
            }
        });
    }

    protected function importBusinesses(string $file): void
    {
        $now = now();
        $this->eachChunk($file, function (array $rows) use ($now) {
            $values = array_map(fn ($r) => [
                'wp_post_id' => (int) $r['wp_post_id'],
                'name' => $r['name'],
                'slug' => $r['slug'],
                'edrpou' => $r['edrpou'] ?: null,
                'short_name' => $r['short_name'] ?: null,
                'address' => $r['address'] ?: null,
                'postal_code' => $r['postal_code'] ?: null,
                'phones' => $r['phones'] ?: null,
                'email' => $r['email'] ?: null,
                'website' => $r['website'] ?: null,
                'latitude' => is_numeric($r['latitude']) ? $r['latitude'] : null,
                'longitude' => is_numeric($r['longitude']) ? $r['longitude'] : null,
                'director' => $r['director'] ?: null,
                'registration_date' => $r['registration_date'] ?: null,
                'kved_codes' => $r['kved_codes'] ?: null,
                'keywords' => $r['keywords'] ?: null,
                'schedule' => $r['schedule'] ?: null,
                'whatsapp' => $r['whatsapp'] ?: null,
                'old_link' => $r['old_link'] ?: null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ], $rows);

            DB::table('businesses')->upsert(
                $values,
                ['wp_post_id'],
                ['name', 'slug', 'edrpou', 'short_name', 'address', 'postal_code', 'phones', 'email',
                    'website', 'latitude', 'longitude', 'director', 'registration_date', 'kved_codes',
                    'keywords', 'schedule', 'whatsapp', 'old_link', 'updated_at']
            );
        });
    }

    protected function importBusinessCategories(string $file, array $businessMap, array $termMap): void
    {
        $now = now();
        $this->eachChunk($file, function (array $rows) use ($businessMap, $termMap, $now) {
            $values = [];
            foreach ($rows as $r) {
                $businessId = $businessMap[(int) $r['wp_post_id']] ?? null;
                $termId = $termMap["{$r['taxonomy']}|{$r['term_slug']}"] ?? null;
                if (! $businessId || ! $termId) {
                    continue;
                }
                $values[] = [
                    'business_id' => $businessId,
                    'taxonomy_term_id' => $termId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($values) {
                DB::table('business_taxonomy_term')->upsert($values, ['business_id', 'taxonomy_term_id'], ['updated_at']);
            }
        });
    }
}
