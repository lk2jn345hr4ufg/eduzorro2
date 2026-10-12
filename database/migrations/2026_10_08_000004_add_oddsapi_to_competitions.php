<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The Odds API (the-odds-api.com) as the odds source: a sport key per
 * competition, and the provider/event id on each stored price.
 */
return new class extends Migration
{
    /** football-data code => The Odds API sport key. */
    protected array $keys = [
        'PL'  => 'soccer_epl',
        'PD'  => 'soccer_spain_la_liga',
        'SA'  => 'soccer_italy_serie_a',
        'BL1' => 'soccer_germany_bundesliga',
        'FL1' => 'soccer_france_ligue_one',
        'DED' => 'soccer_netherlands_eredivisie',
        'PPL' => 'soccer_portugal_primeira_liga',
        'BSA' => 'soccer_brazil_campeonato',
        'CL'  => 'soccer_uefa_champs_league',
        'EC'  => 'soccer_uefa_european_championship',
        'WC'  => 'soccer_fifa_world_cup',
    ];

    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('oddsapi_sport_key', 80)->nullable()->after('apisports_league_id');
        });

        Schema::table('odds', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('fixture_id');
            $table->string('event_id', 64)->nullable()->after('provider');
        });

        foreach ($this->keys as $code => $key) {
            DB::table('competitions')->where('code', $code)->whereNull('oddsapi_sport_key')
                ->update(['oddsapi_sport_key' => $key]);
        }
    }

    public function down(): void
    {
        Schema::table('odds', function (Blueprint $table) {
            $table->dropColumn(['provider', 'event_id']);
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->dropColumn('oddsapi_sport_key');
        });
    }
};
