<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds a third match_type — "subtree" — to the redirects table.
 *
 * Unlike "prefix" (which appends the remaining sub-path onto the target),
 * a "subtree" rule sends the matched path AND everything nested under it to
 * the exact target page, discarding the sub-path. This is what you want to
 * retire a whole section straight to a single page such as the home page.
 *
 * MySQL-specific ENUM change (this app targets MySQL — see README).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `redirects` MODIFY `match_type` ENUM('exact','prefix','subtree') NOT NULL DEFAULT 'exact'");
    }

    public function down(): void
    {
        // Fold any subtree rules back into prefix before shrinking the enum,
        // otherwise MySQL would truncate those values to '' on the ALTER.
        DB::table('redirects')->where('match_type', 'subtree')->update(['match_type' => 'prefix']);

        DB::statement("ALTER TABLE `redirects` MODIFY `match_type` ENUM('exact','prefix') NOT NULL DEFAULT 'exact'");
    }
};
