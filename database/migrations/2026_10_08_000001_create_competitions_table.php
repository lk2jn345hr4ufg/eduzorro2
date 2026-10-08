<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Championships (one top domestic league per country) and tournaments
 * (Champions League, World Cup...) as first-class records.
 *
 * "One league per country" is enforced by the database, not just the admin
 * form: league_country_id mirrors sport_country_id for leagues and stays NULL
 * for cups, and a unique index on it allows any number of NULLs but only one
 * league per country.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->default('league')->index();     // league | cup
            $table->string('code', 12)->unique();                        // football-data code: PL, CL...
            $table->unsignedBigInteger('api_id')->nullable()->index();   // football-data competition id
            $table->unsignedBigInteger('apisports_league_id')->nullable(); // API-Football league id (odds)
            $table->foreignId('sport_country_id')->nullable()->constrained('sport_countries')->nullOnDelete();
            $table->unsignedBigInteger('league_country_id')->nullable()->unique();
            $table->string('slug')->unique();
            $table->json('name');
            $table->text('emblem_url')->nullable();
            $table->string('flag', 32)->nullable();                      // emoji shown next to the country
            $table->boolean('is_featured')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('meta_tabs')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competitions');
    }
};
