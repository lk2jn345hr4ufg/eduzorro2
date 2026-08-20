<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            // The team this transfer was fetched for (API-Football team id).
            $table->unsignedBigInteger('team_api_id')->index();
            $table->string('fingerprint', 64)->unique(); // player+date+in+out, dedupes API repeats

            $table->string('player_name')->nullable();
            $table->date('transfer_date')->nullable()->index();
            $table->string('type')->nullable();

            $table->unsignedBigInteger('in_api_id')->nullable();
            $table->string('in_name')->nullable();
            $table->text('in_logo')->nullable();
            $table->unsignedBigInteger('out_api_id')->nullable();
            $table->string('out_name')->nullable();
            $table->text('out_logo')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
