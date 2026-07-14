<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->unsignedTinyInteger('rating');       // 1..5
            $table->string('title')->nullable();
            $table->text('body');
            $table->boolean('is_approved')->default(false); // moderation queue
            $table->timestamps();

            $table->index(['company_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
