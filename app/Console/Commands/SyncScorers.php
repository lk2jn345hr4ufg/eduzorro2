<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Scorer;
use App\Models\SyncLog;
use App\Services\Football\ApiFootballClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Top scorers per competition from football-data.org (/scorers):
 * one request per competition, ~7 s apart (free plan: 10 requests/minute).
 */
class SyncScorers extends Command
{
    protected $signature = 'sport:sync-scorers
                            {codes?* : Competition codes, e.g. PL PD (default: all championships with a football-data id)}
                            {--season= : Season start year (default: current)}
                            {--limit=20 : Scorers per competition}
                            {--sleep=7 : Seconds between requests}';

    protected $description = 'Import top scorers from football-data.org';

    public function handle(ApiFootballClient $api): int
    {
        $query = Competition::active()->ordered()->whereNotNull('api_id');

        if ($codes = array_map('strtoupper', (array) $this->argument('codes'))) {
            $query->whereIn('code', $codes);
        } else {
            $query->where('type', Competition::LEAGUE);
        }

        $competitions = $query->get();
        $sleep        = max(0, (int) $this->option('sleep'));
        $failed       = 0;

        foreach ($competitions as $i => $competition) {
            if ($i > 0 && $sleep) {
                sleep($sleep);
            }

            $startedAt = now();
            $data      = $api->scorers($competition->code, $this->option('season') ? (int) $this->option('season') : null, (int) $this->option('limit'));
            $rows      = $data['scorers'];
            $season    = (int) ($data['season'] ?: $competition->currentSeason());

            if (! $rows) {
                $failed++;
                $this->warn("→ {$competition->code}: nothing returned (rate limit, token or plan — see laravel.log)");
                SyncLog::record('scorers', $competition->code, 'failed', 0, 'Nothing returned (rate limit, token or plan)', $startedAt);
                continue;
            }

            DB::transaction(function () use ($competition, $season, $rows) {
                Scorer::where('league_code', $competition->code)->where('season', $season)->delete();

                foreach ($rows as $rank => $s) {
                    Scorer::create([
                        'league_code'   => $competition->code,
                        'season'        => $season,
                        'rank'          => $rank + 1,
                        'player_api_id' => $s['player_id'],
                        'player_name'   => mb_substr($s['name'], 0, 255),
                        'nationality'   => $s['nationality'] ? mb_substr($s['nationality'], 0, 80) : null,
                        'team_api_id'   => $s['team_id'],
                        'team_name'     => $s['team'],
                        'team_logo'     => $s['crest'],
                        'goals'         => $s['goals'],
                        'assists'       => is_numeric($s['assists']) ? (int) $s['assists'] : null,
                        'penalties'     => is_numeric($s['penalties']) ? (int) $s['penalties'] : null,
                        'played'        => is_numeric($s['played']) ? (int) $s['played'] : null,
                    ]);
                }

                $competition->forceFill(['scorers_synced_at' => now()])->save();
            });

            $top = $rows[0];
            $this->info("→ {$competition->code}: ".count($rows)." scorers, leader {$top['name']} ({$top['goals']})");
            SyncLog::record('scorers', $competition->code, 'ok', count($rows), "season {$season}: leader {$top['name']} {$top['goals']}", $startedAt);
        }

        return $failed && $failed === $competitions->count() ? self::FAILURE : self::SUCCESS;
    }
}
