<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * uefa.com JSON services (match.uefa.com / standings.uefa.com) as the source
 * for the Nations League and the Europa League: official fixtures, results
 * and tables. Replaces BBC Sport for these two (BBC rows are removed so the
 * same match isn't listed twice).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->unsignedInteger('uefa_competition_id')->nullable()->after('bbc_slug');
            $table->string('uefa_phase', 20)->nullable()->after('uefa_competition_id');
        });

        foreach (['UNL' => 2014, 'EL' => 14] as $code => $id) {
            DB::table('competitions')->where('code', $code)->update([
                'uefa_competition_id' => $id,
                'uefa_phase'          => 'TOURNAMENT',   // skip qualifying rounds
                'bbc_slug'            => null,
            ]);

            DB::table('fixtures')->where('league_code', $code)->where('source', 'bbc')->delete();
            DB::table('standings')->where('league_code', $code)->delete();
        }
    }

    public function down(): void
    {
        DB::table('competitions')->where('code', 'UNL')->update(['bbc_slug' => 'uefa-nations-league']);
        DB::table('competitions')->where('code', 'EL')->update(['bbc_slug' => 'europa-league']);

        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn(['uefa_competition_id', 'uefa_phase']));
    }
};
