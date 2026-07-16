<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // The language this listing's content is written in. Imported
            // WordPress content is Russian; a listing only appears on pages
            // whose locale matches. Default 'ru' backfills existing rows.
            $table->string('language_code', 10)->default('ru')->after('vertical')->index();
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn('language_code');
        });
    }
};
