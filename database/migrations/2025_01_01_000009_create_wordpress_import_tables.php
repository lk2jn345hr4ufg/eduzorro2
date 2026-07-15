<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shared dictionary of every taxonomy term from the old WordPress
        // site (city, section, business, direction, industry, category,
        // categories...) so both listings and businesses can reference the
        // same term catalogue instead of duplicating it per vertical.
        Schema::create('taxonomy_terms', function (Blueprint $table) {
            $table->id();
            $table->string('taxonomy');   // city, section, business, direction, industry, category, categories
            $table->string('slug');
            $table->string('name');
            $table->string('parent_slug')->nullable();
            $table->timestamps();

            $table->unique(['taxonomy', 'slug']);
        });

        // The five review-driven verticals imported from WordPress:
        // courses, online_courses, universities, online_business,
        // affiliate-networks. They all shared the same ACF field shape on
        // the old site, so they share one table here too, distinguished
        // by `vertical`.
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique();
            $table->enum('vertical', ['course', 'online_course', 'university', 'online_business', 'affiliate_network']);
            $table->string('name');
            $table->string('slug');
            $table->string('description_title')->nullable();
            $table->text('description')->nullable();
            $table->string('specialization')->nullable();
            $table->decimal('editorial_rating', 3, 1)->nullable(); // the site admin's own 0-5 score, distinct from user reviews
            $table->text('contacts_text')->nullable();             // freeform phone/email/socials block
            $table->string('website')->nullable();
            $table->string('logo_url')->nullable();                 // still points at eduzorro.com for now
            $table->string('year_of_founded')->nullable();
            $table->text('details_description')->nullable();        // affiliate-network long-form writeup
            $table->string('old_link')->nullable();                 // original WP path, for traceability
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['vertical', 'slug']);
            $table->index(['vertical', 'is_active']);
        });

        Schema::create('listing_taxonomy_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_term_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['listing_id', 'taxonomy_term_id']);
        });

        Schema::create('listing_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('address');
            $table->timestamps();
        });

        Schema::create('listing_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('price')->nullable();
            $table->string('lessons_count')->nullable();
            $table->timestamps();
        });

        Schema::create('listing_pros_cons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->enum('kind', ['pro', 'con']);
            $table->text('text');
            $table->timestamps();
        });

        // Real user reviews, migrated from WordPress comments (each had a
        // `rating` commentmeta value of 1-5).
        Schema::create('listing_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->boolean('is_approved')->default(true); // these were already public comments on the live site
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // The "websites" vertical: a Ukrainian business-registry dataset
        // (EDRPOU-based), structurally unrelated to the reviewed listings
        // above — no ratings/descriptions, just registry facts.
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id')->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('edrpou')->nullable()->index();
            $table->string('short_name', 500)->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phones')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('director')->nullable();
            $table->string('registration_date')->nullable();
            $table->text('kved_codes')->nullable();
            $table->text('keywords')->nullable();
            $table->string('schedule')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('old_link')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('business_taxonomy_term', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('taxonomy_term_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'taxonomy_term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_taxonomy_term');
        Schema::dropIfExists('businesses');
        Schema::dropIfExists('listing_reviews');
        Schema::dropIfExists('listing_pros_cons');
        Schema::dropIfExists('listing_prices');
        Schema::dropIfExists('listing_addresses');
        Schema::dropIfExists('listing_taxonomy_term');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('taxonomy_terms');
    }
};
