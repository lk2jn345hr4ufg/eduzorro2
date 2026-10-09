<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\Football\ApiFootballClient;
use App\Services\Football\FootballStore;
use Illuminate\Console\Command;

/**
 * Team squads (players + coach) from football-data.org /teams/{id}.
 * One request per team; the free tier allows ~10 per minute, hence --sleep.
 */
class SyncSquads extends Command
{
    protected $signature = 'sport:sync-squads
                            {codes?* : Championship codes, e.g. PD SA (default: all)}
                            {--team= : A single team slug}
                            {--missing : Only teams without a stored squad}
                            {--limit=0 : Max teams this run (0 = all)}
                            {--sleep=7 : Seconds between requests}';

    protected $description = 'Import team squads (players, positions, numbers, coach) from football-data.org';

    public function handle(ApiFootballClient $api, FootballStore $store): int
    {
        if (! $api->isConfigured()) {
            $this->error('football-data token is not set (Settings, or FOOTBALL_DATA_TOKEN).');
            return self::FAILURE;
        }

        // Least recently synced first, so a --limit'ed run works through
        // every team over successive runs.
        $query = Team::active()->whereNotNull('api_id')->with('competition')
            ->orderByRaw('squad_synced_at is not null')
            ->orderBy('squad_synced_at')
            ->orderBy('id');

        if ($slug = $this->option('team')) {
            $query->where('slug', $slug);
        }

        if ($codes = array_map('strtoupper', (array) $this->argument('codes'))) {
            $query->whereHas('competition', fn ($q) => $q->whereIn('code', $codes));
        }

        if ($this->option('missing')) {
            $query->whereDoesntHave('players');
        }

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $teams = $query->get();
        $sleep = max(0, (int) $this->option('sleep'));
        $ok    = 0;
        $fails = 0;

        $this->info("Syncing squads for {$teams->count()} team(s)...");

        foreach ($teams as $i => $team) {
            if ($i > 0 && $sleep) {
                sleep($sleep);
            }

            $data  = $api->teamSquad((int) $team->api_id);
            $count = $data ? $store->squad($team, $data['squad'], $data['coach']) : 0;

            if ($count) {
                $ok++;
                $fails = 0;
                $this->line("  ✓ {$team->slug}: {$count} players".($data['coach'] ? ", coach {$data['coach']['name']}" : ''));
            } else {
                $this->warn("  – {$team->slug}: no squad returned (rate limit or not in plan; see laravel.log)");

                // Three empty answers in a row: rate limit or token problem —
                // stop instead of hammering the API.
                if (++$fails >= 3) {
                    $this->error('  Stopping after 3 failures in a row. Try again in a minute, or check the token.');
                    break;
                }
            }
        }

        $this->newLine();
        $this->info("Done. {$ok}/{$teams->count()} squad(s) stored.");

        return self::SUCCESS;
    }
}
