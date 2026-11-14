<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** When sport:auto-sync last refreshed a competition (rotation queue). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->timestamp('auto_synced_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn('auto_synced_at'));
    }
};
