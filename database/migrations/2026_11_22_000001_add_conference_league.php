<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * UEFA Conference League (/{language}/conference-league), right after the
 * Europa League. Source: uefa.com JSON (competitionId 2019, main phase only),
 * odds: The Odds API soccer_uefa_europa_conference_league.
 */
return new class extends Migration
{
    public function up(): void
    {
        // National-team tournaments (labelled "National teams" on the home page).
        DB::table('competitions')->whereIn('code', ['EC', 'WC', 'UNL', 'EQ'])->update(['is_national' => true]);

        if (DB::table('competitions')->where('code', 'ECL')->exists()) {
            return;
        }

        $el    = DB::table('competitions')->where('code', 'EL')->first();
        $after = (int) ($el->sort_order ?? DB::table('competitions')->max('sort_order') ?? 0);

        DB::table('competitions')->where('sort_order', '>', $after)->increment('sort_order');

        DB::table('competitions')->insert([
            'type'                => 'cup',
            'is_national'         => false,
            'code'                => 'ECL',
            'uefa_competition_id' => 2019,
            'uefa_phase'          => 'TOURNAMENT',
            'oddsapi_sport_key'   => 'soccer_uefa_europa_conference_league',
            'slug'                => 'conference-league',
            'name'                => json_encode([
                'en' => 'UEFA Conference League',
                'uk' => 'Ліга конференцій УЄФА',
                'ru' => 'Лига конференций УЕФА',
                'es' => 'Liga Conferencia de la UEFA',
            ], JSON_UNESCAPED_UNICODE),
            'flag'                => '🇪🇺',
            'is_featured'         => true,
            'is_active'           => true,
            'sort_order'          => $after + 1,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('competitions')->where('code', 'ECL')->delete();
    }
};
