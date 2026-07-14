<?php

namespace App\Console\Commands;

use App\Models\Redirect;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-imports 301 redirects from database/import/redirects.csv (generated
 * from the WordPress export: one row per migrated listing/business, plus
 * one per taxonomy archive page). Uses chunked upserts rather than the
 * admin panel's CSV importer, which isn't built for 14k+ rows in one
 * HTTP request.
 */
class ImportRedirects extends Command
{
    protected $signature = 'import:redirects {--path=} {--chunk=500}';

    protected $description = 'Bulk-import 301 redirects from database/import/redirects.csv';

    public function handle(): int
    {
        $file = $this->option('path') ?: database_path('import/redirects.csv');
        $chunkSize = (int) $this->option('chunk');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);
        $chunk = [];
        $total = 0;
        $now = now();

        $flush = function () use (&$chunk, $now, &$total) {
            if (! $chunk) {
                return;
            }
            $values = array_map(fn ($r) => [
                'from_path' => $r['from_path'],
                'to_path' => $r['to_path'],
                'status_code' => (int) $r['status_code'],
                'match_type' => $r['match_type'],
                'is_active' => (bool) $r['is_active'],
                'notes' => $r['notes'] ?: null,
                'hits' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ], $chunk);

            DB::table('redirects')->upsert(
                $values,
                ['from_path'],
                ['to_path', 'status_code', 'match_type', 'is_active', 'notes', 'updated_at']
            );
            $total += count($values);
            $chunk = [];
        };

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                continue;
            }
            $chunk[] = array_combine($header, $row);
            if (count($chunk) >= $chunkSize) {
                $flush();
            }
        }
        $flush();
        fclose($handle);

        Cache::forget(Redirect::CACHE_KEY);

        $this->info("Imported {$total} redirects.");

        return self::SUCCESS;
    }
}
