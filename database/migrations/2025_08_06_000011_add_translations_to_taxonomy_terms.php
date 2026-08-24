<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WordPress-imported terms only carry a single plain `name`, which is why the
 * site shows those categories in one language. This adds an optional JSON
 * column of translations; `name` stays untouched so re-imports keep matching.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxonomy_terms', function (Blueprint $table) {
            $table->json('name_i18n')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('taxonomy_terms', function (Blueprint $table) {
            $table->dropColumn('name_i18n');
        });
    }
};
