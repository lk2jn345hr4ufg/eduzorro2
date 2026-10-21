<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Local time zone of each championship, used to show kick-off times in the
 * match's own zone (a cup match takes the home club's championship zone).
 */
return new class extends Migration
{
    protected array $zones = [
        'PL'  => 'Europe/London',
        'PD'  => 'Europe/Madrid',
        'SA'  => 'Europe/Rome',
        'BL1' => 'Europe/Berlin',
        'FL1' => 'Europe/Paris',
        'DED' => 'Europe/Amsterdam',
        'PPL' => 'Europe/Lisbon',
        'BSA' => 'America/Sao_Paulo',
        'UPL' => 'Europe/Kyiv',
    ];

    public function up(): void
    {
        Schema::table('competitions', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('flag');
        });

        foreach ($this->zones as $code => $zone) {
            DB::table('competitions')->where('code', $code)->whereNull('timezone')->update(['timezone' => $zone]);
        }
    }

    public function down(): void
    {
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn('timezone'));
    }
};
