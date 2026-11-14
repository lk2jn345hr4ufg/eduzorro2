<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\SyncLog;
use App\Models\Team;
use App\Services\Football\ApiSportsTransfersClient;
use App\Services\Odds\OddsApiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

/**
 * Rotating automatic sync. Scheduled every 15 minutes; each run picks ONE
 * competition — the one auto-synced longest ago, if older than
 * football.auto_sync.every_hours — and refreshes everything about it:
 *
 *   matches & table   football-data / uefa.com / BBC (whichever it has)
 *   top scorers       championships with a football-data id
 *   odds              when it has an odds key and the provider is set up
 *   squads, transfers championships, at most once a week each
 *
 * So championships are updated one at a time with a 15-minute pause, each
 * once a day. Every step is logged in the dashboard's Sync log.
 */
class AutoSync extends Command
{
    protected $signature = 'sport:auto-sync
                            {--code= : Sync this competition now, ignoring the queue}
                            {--dry-run : Only show which competition is next}
                            {--force : Run even when auto sync is switched off}';

    protected $description = 'Refresh the next competition in the daily rotation';

    public function handle(ApiSportsTransfersClient $apiSports, OddsApiClient $oddsApi): int
    {
        if (! config('football.auto_sync.enabled') && ! $this->option('force') && ! $this->option('code')) {
            $this->line('Auto sync is switched off (Settings → Auto sync).');
            return self::SUCCESS;
        }

        $competition = $this->option('code')
            ? Competition::where('code', strtoupper($this->option('code')))->first()
            : self::nextDue();

        if (! $competition) {
            $this->line($this->option('code') ? 'No such competition.' : 'Nothing due: every competition was synced within the last '.config('football.auto_sync.every_hours').' h.');
            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("Next: {$competition->code} ".$competition->translate('name')
                .' (last auto sync: '.($competition->auto_synced_at?->diffForHumans() ?? 'never').')');
            $this->line('Steps: '.implode(', ', array_keys($this->steps($competition, $apiSports, $oddsApi))));
            return self::SUCCESS;
        }

        // Claim it first, so an overlapping run can't pick the same one.
        $competition->forceFill(['auto_synced_at' => now()])->save();

        $startedAt = now();
        $done      = [];
        $failed    = [];

        $this->info("→ {$competition->code} ".$competition->translate('name'));

        foreach ($this->steps($competition, $apiSports, $oddsApi) as $label => [$command, $params]) {
            try {
                $code   = Artisan::call($command, $params);
                $output = trim(Artisan::output());
            } catch (\Throwable $e) {
                $code   = 1;
                $output = $e->getMessage();
            }

            $this->line("   {$label}: ".($code === 0 ? 'ok' : 'FAILED'));

            if ($this->output->isVerbose() && $output !== '') {
                $this->line(preg_replace('/^/m', '      ', Str::limit($output, 1500)));
            }

            $code === 0 ? $done[] = $label : $failed[] = $label;

            sleep(3); // small pause between providers' requests
        }

        SyncLog::record('auto', $competition->code,
            $failed ? ($done ? 'partial' : 'failed') : 'ok',
            count($done),
            'auto: '.implode(', ', $done).($failed ? ' · failed: '.implode(', ', $failed) : ''),
            $startedAt
        );

        return self::SUCCESS;
    }

    /** Active competitions that have a data source, in queue order. */
    public static function queue()
    {
        return Competition::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNotNull('api_id')->orWhereNotNull('uefa_competition_id')
                ->orWhere(fn ($q) => $q->whereNotNull('bbc_slug')->where('bbc_slug', '!=', '')))
            ->orderByRaw('auto_synced_at is not null')
            ->orderBy('auto_synced_at')
            ->orderBy('sort_order');
    }

    /** The competition auto-synced longest ago, if it is due. */
    public static function nextDue(): ?Competition
    {
        $due = now()->subHours(max(1, (int) config('football.auto_sync.every_hours', 24)))->addMinutes(10);

        return self::queue()
            ->where(fn ($q) => $q->whereNull('auto_synced_at')->orWhere('auto_synced_at', '<', $due))
            ->first();
    }

    /** @return array<string, array{0: string, 1: array}> label => [command, params] */
    protected function steps(Competition $c, ApiSportsTransfersClient $apiSports, OddsApiClient $oddsApi): array
    {
        $steps  = [];
        $weekly = now()->subDays((int) config('football.auto_sync.weekly_days', 7));
        $league = $c->type === Competition::LEAGUE;

        if ($c->api_id) {
            $steps['matches & table'] = ['sport:sync-competition', ['codes' => [$c->code], '--sleep' => 0]];
        }

        if ($c->uefa_competition_id) {
            $steps['matches & table (UEFA)'] = ['sport:sync-uefa', ['codes' => [$c->code], '--delay' => 0]];
        }

        if ($c->bbc_slug) {
            $steps['matches (BBC)'] = ['sport:sync-bbc', ['codes' => [$c->code], '--delay' => 3]];
        }

        if ($league && $c->api_id) {
            $steps['top scorers'] = ['sport:sync-scorers', ['codes' => [$c->code], '--sleep' => 0]];
        }

        $oddsProvider = config('odds.provider', 'oddsapi');
        if (($oddsProvider === 'oddsapi' && $c->oddsapi_sport_key && $oddsApi->isConfigured())
            || ($oddsProvider === 'apisports' && $c->apisports_league_id && $apiSports->isConfigured())) {
            $steps['odds'] = ['sport:sync-odds', ['codes' => [$c->code], '--days' => 3]];
        }

        if ($league && $c->api_id) {
            $teams = Team::where('competition_id', $c->id)->where('is_active', true)->whereNotNull('api_id');

            if ((clone $teams)->where(fn ($q) => $q->whereNull('squad_synced_at')->orWhere('squad_synced_at', '<', $weekly))->exists()) {
                $steps['squads'] = ['sport:sync-squads', ['codes' => [$c->code], '--limit' => (int) config('football.auto_sync.squads_limit', 25), '--sleep' => 7]];
            }

            if ($c->country && $apiSports->isConfigured()
                && (clone $teams)->where(fn ($q) => $q->whereNull('transfers_synced_at')->orWhere('transfers_synced_at', '<', $weekly))->exists()) {
                $steps['transfers'] = ['sport:sync-transfers', ['--country' => $c->country->slug, '--limit' => (int) config('football.auto_sync.transfers_limit', 10)]];
            }
        }

        return $steps;
    }
}
