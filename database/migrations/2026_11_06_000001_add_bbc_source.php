<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BBC Sport as a fixtures source for competitions football-data's free plan
 * doesn't cover (Europa League): competitions.bbc_slug says which BBC page to
 * read, fixtures.source records where a match came from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('bbc_slug', 80)->nullable()->after('oddsapi_sport_key');
        });

        Schema::table('fixtures', function (Blueprint $table) {
            $table->string('source', 20)->nullable()->after('api_id');
        });

        // Europa League: fixtures from BBC. Its football-data id is removed so
        // sport:sync-competition stops hitting a competition the free plan
        // refuses (put 2146 back in the admin after upgrading the plan).
        DB::table('competitions')->where('code', 'EL')->update(['bbc_slug' => 'europa-league', 'api_id' => null]);
    }

    public function down(): void
    {
        DB::table('competitions')->where('code', 'EL')->update(['api_id' => 2146]);

        Schema::table('fixtures', fn (Blueprint $table) => $table->dropColumn('source'));
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn('bbc_slug'));
    }
};
