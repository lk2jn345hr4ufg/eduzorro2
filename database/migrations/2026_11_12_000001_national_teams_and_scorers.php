<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - scorers: top scorers per competition season (football-data /scorers);
 * - national-team tournaments from BBC Sport: UEFA Nations League and
 *   Euro 2028 qualifying (group tables are computed from the results).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scorers', function (Blueprint $table) {
            $table->id();
            $table->string('league_code', 16)->index();
            $table->unsignedSmallInteger('season');
            $table->unsignedSmallInteger('rank');
            $table->unsignedBigInteger('player_api_id')->nullable();
            $table->string('player_name');
            $table->string('nationality', 80)->nullable();
            $table->unsignedBigInteger('team_api_id')->nullable()->index();
            $table->string('team_name')->nullable();
            $table->text('team_logo')->nullable();
            $table->unsignedSmallInteger('goals')->default(0);
            $table->unsignedSmallInteger('assists')->nullable();
            $table->unsignedSmallInteger('penalties')->nullable();
            $table->unsignedSmallInteger('played')->nullable();
            $table->timestamps();

            $table->index(['league_code', 'season', 'rank']);
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->boolean('is_national')->default(false)->after('type');
            $table->timestamp('scorers_synced_at')->nullable();
        });

        DB::table('competitions')->whereIn('code', ['EC', 'WC'])->update(['is_national' => true]);

        $base = max(100, (int) DB::table('competitions')->where('type', 'cup')->max('sort_order'));

        $add = [
            'UNL' => [
                'slug' => 'nations-league', 'bbc' => 'uefa-nations-league',
                'name' => ['en' => 'UEFA Nations League', 'uk' => 'Ліга націй УЄФА', 'ru' => 'Лига наций УЕФА', 'es' => 'Liga de Naciones de la UEFA'],
            ],
            'EQ' => [
                'slug' => 'euro-2028-qualifying', 'bbc' => 'euro-qualifying',
                'name' => ['en' => 'Euro 2028 qualifying', 'uk' => 'Відбір до Євро-2028', 'ru' => 'Отбор к Евро-2028', 'es' => 'Clasificación Eurocopa 2028'],
            ],
        ];

        $i = 0;
        foreach ($add as $code => $c) {
            if (DB::table('competitions')->where('code', $code)->exists()) {
                continue;
            }

            DB::table('competitions')->insert([
                'type'        => 'cup',
                'is_national' => true,
                'code'        => $code,
                'slug'        => $c['slug'],
                'bbc_slug'    => $c['bbc'],
                'name'        => json_encode($c['name'], JSON_UNESCAPED_UNICODE),
                'flag'        => '🇪🇺',
                'is_featured' => true,
                'is_active'   => true,
                'sort_order'  => $base + (++$i),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('competitions')->whereIn('code', ['UNL', 'EQ'])->delete();

        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn(['is_national', 'scorers_synced_at']));
        Schema::dropIfExists('scorers');
    }
};
