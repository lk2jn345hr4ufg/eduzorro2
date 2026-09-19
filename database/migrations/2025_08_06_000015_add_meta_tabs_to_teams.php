<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A team record drives five URLs (news, fixtures, euro cups, transfers,
 * standings). meta_title/meta_description cover the team as a whole; this adds
 * optional per-tab overrides so each URL can have its own tags.
 *
 * Shape: {"fixtures": {"title": {"ru": "..."}, "description": {"ru": "..."}}}
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('teams', 'meta_tabs')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->json('meta_tabs')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('teams', 'meta_tabs')) {
            return;
        }

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('meta_tabs');
        });
    }
};
