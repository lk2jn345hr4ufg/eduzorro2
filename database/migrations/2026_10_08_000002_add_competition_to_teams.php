<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('competition_id')->nullable()->after('sport_country_id')
                ->constrained('competitions')->nullOnDelete();
            $table->boolean('is_popular')->default(false)->index();
            $table->unsignedInteger('popular_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('competition_id');
            $table->dropColumn(['is_popular', 'popular_order']);
        });
    }
};
