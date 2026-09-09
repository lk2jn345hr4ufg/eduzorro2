<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teams are now identified by football-data ids, but transfers still come from
 * api-sports, which uses its own ids. Keep that id alongside so the two
 * providers can be joined per team.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->unsignedBigInteger('apisports_id')->nullable()->after('api_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('apisports_id');
        });
    }
};
