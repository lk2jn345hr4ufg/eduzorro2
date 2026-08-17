<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// GNews/NewsAPI image URLs can exceed 255 chars, so widen the URL columns.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table("team_news", function (Blueprint $table) {
            $table->text("image_url")->nullable()->change();
            $table->text("source_url")->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table("team_news", function (Blueprint $table) {
            $table->string("image_url")->nullable()->change();
            $table->string("source_url")->nullable()->change();
        });
    }
};
