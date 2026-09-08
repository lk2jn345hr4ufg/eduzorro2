<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\TeamNews;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * The switch to football-data.org created near-duplicate teams, because the new
 * provider spells clubs with a suffix ("Manchester United FC") while the rows
 * imported from api-sports did not.
 *
 * This merges each new row into the matching old one: the old record keeps its
 * id, slug and attached news (so URLs and relations survive) but takes over the
 * new provider's api_id, competition code, crest and venue. The duplicate is
 * then deleted.
 */
class MergeDuplicateTeams extends Command
{
    protected $signature = 'sport:merge-teams {--dry-run : Show what would happen without changing anything}';

    protected $description = 'Merge duplicate teams created when switching football data provider';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // New rows are the ones carrying a football-data competition code.
        $fresh = Team::whereNotNull('primary_league_code')->get();

        if ($fresh->isEmpty()) {
            $this->warn('No teams with a competition code found — run sport:sync-football first.');
            return self::SUCCESS;
        }

        $merged = 0;
        $kept   = 0;

        foreach ($fresh as $new) {
            $slug = $this->slugFor($new->translate('name') ?: $new->slug);

            $old = Team::where('sport_country_id', $new->sport_country_id)
                ->whereNull('primary_league_code')
                ->whereKeyNot($new->getKey())
                // Old rows are often shorter ("newcastle" vs "newcastle-united"),
                // so match a prefix in either direction, longest slug first.
                ->where(fn ($q) => $q
                    ->where('slug', $slug)
                    ->orWhere('slug', 'like', $slug.'%')
                    ->orWhereRaw('? like concat(slug, \'%\')', [$slug]))
                ->orderByRaw('char_length(slug) desc')
                ->first();

            if (! $old) {
                $kept++;
                continue;
            }

            $this->line("  {$new->slug}  →  {$old->slug}  (api_id {$old->api_id} → {$new->api_id})");

            if ($dry) {
                $merged++;
                continue;
            }

            // Old record wins on identity, new one wins on provider data.
            $old->update([
                'api_id'                => $new->api_id,
                'primary_league_api_id' => $new->primary_league_api_id,
                'primary_league_code'   => $new->primary_league_code,
                'logo_url'              => $new->logo_url ?: $old->logo_url,
                'short_name'            => $new->short_name ?: $old->short_name,
                'stadium'               => $new->stadium ?: $old->stadium,
                'founded'               => $new->founded ?: $old->founded,
                'is_active'             => true,
            ]);

            // Move any news that was already attached to the duplicate.
            TeamNews::where('team_id', $new->id)->update(['team_id' => $old->id]);

            $new->delete();
            $merged++;
        }

        $this->newLine();
        $this->info($dry
            ? "Dry run: {$merged} would be merged, {$kept} left as new teams."
            : "Merged {$merged} duplicate(s). {$kept} stayed as new teams.");

        return self::SUCCESS;
    }

    protected function slugFor(string $name): string
    {
        $clean = preg_replace(
            '/\b(fc|cf|afc|sc|ac|as|ss|ssc|bk|if|sk|vfl|vfb|tsv|fsv|rc|cd|ud|sd)\b/iu',
            ' ',
            $name
        );

        return Str::slug(trim(preg_replace('/\s+/u', ' ', $clean)));
    }
}
