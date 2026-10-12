<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data-freshness tracking for the admin dashboard:
 *  - sync_logs: one row per sync run and scope (what, for what, when, result);
 *  - "last synced" timestamps on competitions and teams.
 *
 * Existing data is backfilled from the rows already stored, so the dashboard
 * shows real dates right after migrating.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();        // teams|matches|squads|transfers|odds
            $table->string('scope', 80)->nullable();     // competition code, country slug, team slug or "all"
            $table->string('status', 10)->default('ok'); // ok|partial|failed|skipped
            $table->unsignedInteger('items')->default(0);
            $table->string('message', 500)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->timestamp('teams_synced_at')->nullable();
            $table->timestamp('matches_synced_at')->nullable();
            $table->timestamp('odds_synced_at')->nullable();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->timestamp('transfers_synced_at')->nullable();
        });

        // ---- Backfill from what is already stored -------------------------
        foreach (DB::table('competitions')->get(['id', 'code']) as $c) {
            DB::table('competitions')->where('id', $c->id)->update([
                'matches_synced_at' => max(
                    DB::table('fixtures')->where('league_code', $c->code)->max('updated_at'),
                    DB::table('standings')->where('league_code', $c->code)->max('updated_at'),
                ) ?: null,
                'teams_synced_at' => DB::table('teams')->where('competition_id', $c->id)->max('updated_at'),
                'odds_synced_at'  => DB::table('odds')
                    ->join('fixtures', 'fixtures.id', '=', 'odds.fixture_id')
                    ->where('fixtures.league_code', $c->code)
                    ->max('odds.fetched_at'),
            ]);
        }

        foreach (DB::table('teams')->get(['id', 'api_id', 'apisports_id']) as $t) {
            $id = $t->apisports_id ?: $t->api_id;

            if ($id && ($last = DB::table('transfers')->where('team_api_id', $id)->max('updated_at'))) {
                DB::table('teams')->where('id', $t->id)->update(['transfers_synced_at' => $last]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('teams', fn (Blueprint $table) => $table->dropColumn('transfers_synced_at'));
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn(['teams_synced_at', 'matches_synced_at', 'odds_synced_at']));
        Schema::dropIfExists('sync_logs');
    }
};
