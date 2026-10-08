<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1X2 ("Match Winner") odds per match, from API-Football. One row per fixture:
 * a refresh overwrites it, so the table never grows beyond the fixture count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixture_id')->unique()->constrained('fixtures')->cascadeOnDelete();
            $table->unsignedBigInteger('apisports_fixture_id')->nullable()->index();
            $table->string('bookmaker')->nullable();
            $table->decimal('home', 7, 2)->nullable();
            $table->decimal('draw', 7, 2)->nullable();
            $table->decimal('away', 7, 2)->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odds');
    }
};
