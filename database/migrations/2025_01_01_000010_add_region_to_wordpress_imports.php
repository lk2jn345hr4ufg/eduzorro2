<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('wp_post_id')->constrained()->nullOnDelete();
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('wp_post_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
        Schema::table('listings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
