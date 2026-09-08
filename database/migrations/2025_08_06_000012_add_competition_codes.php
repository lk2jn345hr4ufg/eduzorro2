<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * football-data.org addresses competitions by code (PL, PD, CL...), while our
 * tables link them by numeric id. Store both: the code to call the API, the id
 * to join fixtures/standings to a team.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('primary_league_code', 10)->nullable()->after('primary_league_api_id');
        });

        Schema::table('fixtures', function (Blueprint $table) {
            $table->string('league_code', 10)->nullable()->after('league_api_id')->index();
        });

        Schema::table('standings', function (Blueprint $table) {
            $table->string('league_code', 10)->nullable()->after('league_api_id');
        });
    }

    public function down(): void
    {
        Schema::table('teams', fn (Blueprint $t) => $t->dropColumn('primary_league_code'));
        Schema::table('fixtures', fn (Blueprint $t) => $t->dropColumn('league_code'));
        Schema::table('standings', fn (Blueprint $t) => $t->dropColumn('league_code'));
    }
};
