<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Stadium of a match, when football-data provides it (neutral venues, finals). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixtures', function (Blueprint $table) {
            $table->string('venue', 120)->nullable()->after('away_logo');
        });
    }

    public function down(): void
    {
        Schema::table('fixtures', fn (Blueprint $table) => $table->dropColumn('venue'));
    }
};
