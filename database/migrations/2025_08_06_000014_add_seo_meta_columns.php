<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-page SEO overrides. Both columns are translatable JSON like the rest of
 * the content; when a value is empty the page keeps using its generated title
 * and description, so nothing changes until an editor fills something in.
 */
return new class extends Migration
{
    protected array $tables = [
        'regions', 'industries', 'categories',
        'sports', 'sport_countries', 'teams', 'tools', 'team_news',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'meta_title')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->json('meta_title')->nullable();
                $t->json('meta_description')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'meta_title')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['meta_title', 'meta_description']);
            });
        }
    }
};
