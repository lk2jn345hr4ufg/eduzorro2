<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('league_api_id');
            $table->unsignedSmallInteger('season');
            $table->unsignedBigInteger('team_api_id');

            $table->unsignedSmallInteger('rank')->nullable();
            $table->string('team_name')->nullable();
            $table->text('team_logo')->nullable();
            $table->string('group_label')->nullable();
            $table->string('form', 20)->nullable();

            $table->unsignedSmallInteger('played')->default(0);
            $table->unsignedSmallInteger('win')->default(0);
            $table->unsignedSmallInteger('draw')->default(0);
            $table->unsignedSmallInteger('lose')->default(0);
            $table->smallInteger('goals_for')->default(0);
            $table->smallInteger('goals_against')->default(0);
            $table->smallInteger('points')->default(0);

            $table->timestamps();

            $table->unique(['league_api_id', 'season', 'team_api_id'], 'standings_league_season_team_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standings');
    }
};
