<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path');                 // normalized, root-relative, e.g. /old-page
            $table->string('to_path');                    // relative path or absolute URL
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->enum('match_type', ['exact', 'prefix'])->default('exact');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique('from_path');
            $table->index(['is_active', 'match_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
