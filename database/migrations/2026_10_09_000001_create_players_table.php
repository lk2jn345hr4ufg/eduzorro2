<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Team squads from football-data.org (/competitions/{code}/teams and
 * /teams/{id}). A sync replaces a team's whole squad, so players who left
 * disappear from the list.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedBigInteger('api_id')->nullable();
            $table->string('name');
            $table->string('position', 40)->nullable();   // as given: "Goalkeeper", "Centre-Back", "Midfield"…
            $table->string('line', 3)->nullable()->index(); // GK / DEF / MID / FWD
            $table->unsignedSmallInteger('shirt_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('contract_until', 10)->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'api_id']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->string('coach_name')->nullable();
            $table->string('coach_nationality', 80)->nullable();
            $table->timestamp('squad_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['coach_name', 'coach_nationality', 'squad_synced_at']);
        });

        Schema::dropIfExists('players');
    }
};
