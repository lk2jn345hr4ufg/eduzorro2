<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Which languages a region actually supports. Previously any active
        // language worked with any active region; real content needs this
        // constrained (e.g. Kazakhstan content only exists in Russian).
        Schema::create('language_region', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['region_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_region');
    }
};
