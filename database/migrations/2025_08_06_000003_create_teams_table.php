<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained('sports')->cascadeOnDelete();
            $table->foreignId('sport_country_id')->constrained('sport_countries')->cascadeOnDelete();
            $table->unsignedBigInteger('api_id')->nullable()->index(); // API-Football team id
            $table->unsignedBigInteger('primary_league_api_id')->nullable(); // domestic league, for standings tab
            $table->string('slug');
            $table->json('name');                  // translatable
            $table->string('short_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->unsignedSmallInteger('founded')->nullable();
            $table->string('stadium')->nullable();
            $table->string('city')->nullable();
            $table->string('website')->nullable();
            $table->json('description')->nullable(); // translatable
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['sport_country_id', 'slug']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
