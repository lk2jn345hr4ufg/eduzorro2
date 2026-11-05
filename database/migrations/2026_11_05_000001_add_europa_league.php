<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * UEFA Europa League as a tournament (/{language}/europa-league), placed
 * right after the Champions League.
 *
 * Data sources: football-data.org code EL (id 2146) — NOT in its free plan,
 * so matches/tables arrive only on a paid football-data plan; The Odds API
 * key soccer_uefa_europa_league (odds work on the free plan); API-Football
 * league 3 (transfers/odds fallback).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('competitions')->where('code', 'EL')->exists()) {
            return;
        }

        $cl    = DB::table('competitions')->where('code', 'CL')->first();
        $after = $cl->sort_order ?? DB::table('competitions')->max('sort_order');

        // Make room right after the Champions League.
        DB::table('competitions')->where('sort_order', '>', $after)->increment('sort_order');

        DB::table('competitions')->insert([
            'type'                => 'cup',
            'code'                => 'EL',
            'api_id'              => 2146,
            'apisports_league_id' => 3,
            'oddsapi_sport_key'   => 'soccer_uefa_europa_league',
            'sport_country_id'    => null,
            'league_country_id'   => null,
            'slug'                => 'europa-league',
            'name'                => json_encode([
                'en' => 'UEFA Europa League',
                'uk' => 'Ліга Європи УЄФА',
                'ru' => 'Лига Европы УЕФА',
                'es' => 'Liga Europa',
            ], JSON_UNESCAPED_UNICODE),
            'emblem_url'          => 'https://crests.football-data.org/EL.png',
            'flag'                => '🇪🇺',
            'is_featured'         => true,
            'sort_order'          => $after + 1,
            'is_active'           => true,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('competitions')->where('code', 'EL')->delete();
    }
};
