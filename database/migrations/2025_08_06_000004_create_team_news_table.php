<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// API-Football has no news endpoint, so team news is stored locally
// (editable in the admin) rather than synced. See APPLY.md.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_news', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('slug');
            $table->json('title');                 // translatable
            $table->json('excerpt')->nullable();    // translatable
            $table->json('body')->nullable();       // translatable
            $table->string('image_url')->nullable();
            $table->string('source_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'slug']);
            $table->index(['is_active', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_news');
    }
};
