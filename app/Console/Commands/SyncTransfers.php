<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Transfer;
use App\Services\Football\ApiSportsTransfersClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Transfers come from api-sports, everything else from football-data.org.
 * Each team's api-sports id is resolved once by name and cached on the row
 * (teams.apisports_id), so later runs go straight to /transfers.
 */
class SyncTransfers extends Command
{
    protected $signature = 'sport:sync-transfers
                            {--team= : Limit to a single team slug}
                            {--country= : Limit to one country (slug, e.g. england)}
                            {--limit=0 : Max teams this run (0 = all)}
                            {--sleep=0 : Extra seconds between teams (the client already throttles itself)}
                            {--relink : Re-resolve api-sports ids even if already stored}
                            {--debug : Print what each name lookup returned}';

    protected $description = 'Import transfers from api-sports for teams sourced from football-data.org';

    public function handle(ApiSportsTransfersClient $api): int
    {
        if (! $api->isConfigured()) {
            $this->error('API_FOOTBALL_KEY is not set (Settings → Transfers, or .env).');
            return self::FAILURE;
        }

        $query = Team::query()->active()->ordered()->with('country');

        if ($slug = $this->option('team')) {
            $query->where('slug', $slug);
        }

        if ($countrySlug = $this->option('country')) {
            $query->whereHas('country', fn ($q) => $q->where('slug', $countrySlug));
        }

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $teams = $query->get();

        if ($teams->isEmpty()) {
            $this->warn('No matching teams.');
            return self::SUCCESS;
        }

        $sleep   = (int) $this->option('sleep');
        $relink  = (bool) $this->option('relink');
        $stored  = 0;
        $blank   = 0;

        $this->info("Syncing transfers for {$teams->count()} team(s)...");

        foreach ($teams as $team) {
            $name = $team->translate('name') ?: $team->slug;

            // Resolve (once) which api-sports id this football-data team is.
            $apiSportsId = $relink ? null : $team->apisports_id;

            if (! $apiSportsId) {
                $country     = $team->country?->api_name ?: $team->country?->translate('name');
                $debug       = (bool) $this->option('debug');

                $apiSportsId = $api->resolveTeamId(
                    $name,
                    $country,
                    $debug ? fn (string $line) => $this->line("   {$line}") : null
                );

                if ($apiSportsId) {
                    $team->update(['apisports_id' => $apiSportsId]);
                    $this->line("→ {$name}: linked to api-sports id {$apiSportsId}");
                } else {
                    $this->warn("→ {$name}: no api-sports match, skipped (run with --debug to see the lookups)");
                    continue;
                }
            } else {
                $this->line("→ {$name} (api-sports {$apiSportsId})");
            }

            $rows = $api->transfers($apiSportsId);
            $n    = $this->store($rows, $apiSportsId);
            $stored += $n;

            if ($n === 0) {
                $blank++;
            }

            $this->line("   transfers: {$n}");

            // Five empty answers in a row usually means quota, not "no data".
            if ($blank >= 5) {
                $this->error('Five empty responses in a row — stopping. Check the quota or key.');
                break;
            }

            if ($n > 0) {
                $blank = 0;
            }

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        $this->newLine();
        $this->info("Done. {$stored} transfer row(s) upserted.");

        return self::SUCCESS;
    }

    protected function store(array $rows, int $teamApiId): int
    {
        $n = 0;

        foreach ($rows as $row) {
            $player = data_get($row, 'player.name');

            foreach ((array) data_get($row, 'transfers', []) as $t) {
                $date  = data_get($t, 'date');
                $inId  = data_get($t, 'teams.in.id');
                $outId = data_get($t, 'teams.out.id');

                if (! $player || ! $date) {
                    continue;
                }

                Transfer::updateOrCreate(
                    ['fingerprint' => sha1("{$teamApiId}|{$player}|{$date}|{$inId}|{$outId}")],
                    [
                        'team_api_id'   => $teamApiId,
                        'player_name'   => $player,
                        'transfer_date' => $this->date($date),
                        'type'          => data_get($t, 'type'),
                        'in_api_id'     => $inId,
                        'in_name'       => data_get($t, 'teams.in.name'),
                        'in_logo'       => data_get($t, 'teams.in.logo'),
                        'out_api_id'    => $outId,
                        'out_name'      => data_get($t, 'teams.out.name'),
                        'out_logo'      => data_get($t, 'teams.out.logo'),
                    ]
                );

                $n++;
            }
        }

        return $n;
    }

    protected function date(?string $raw): ?Carbon
    {
        if (! $raw) {
            return null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
