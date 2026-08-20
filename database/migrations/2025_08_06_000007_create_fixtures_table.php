<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixtures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('api_id')->unique();   // API-Football fixture id
            $table->unsignedBigInteger('league_api_id')->index();
            $table->string('league_name')->nullable();
            $table->string('league_round')->nullable();
            $table->unsignedSmallInteger('season')->index();

            $table->unsignedBigInteger('home_api_id')->index();
            $table->string('home_name')->nullable();
            $table->text('home_logo')->nullable();
            $table->unsignedBigInteger('away_api_id')->index();
            $table->string('away_name')->nullable();
            $table->text('away_logo')->nullable();

            $table->unsignedTinyInteger('goals_home')->nullable();
            $table->unsignedTinyInteger('goals_away')->nullable();
            $table->string('status_short', 10)->nullable();
            $table->timestamp('kickoff_at')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixtures');
    }
};
