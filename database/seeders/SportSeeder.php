<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $sports = [
            ['slug' => 'football',   'name' => ['en' => 'Football',   'uk' => 'Футбол',       'ru' => 'Футбол',        'es' => 'Fútbol'],     'has_competitions' => true,  'sort_order' => 0],
            ['slug' => 'basketball', 'name' => ['en' => 'Basketball', 'uk' => 'Баскетбол',    'ru' => 'Баскетбол',     'es' => 'Baloncesto'], 'has_competitions' => false, 'sort_order' => 1],
            ['slug' => 'tennis',     'name' => ['en' => 'Tennis',     'uk' => 'Теніс',        'ru' => 'Теннис',        'es' => 'Tenis'],      'has_competitions' => false, 'sort_order' => 2],
            ['slug' => 'hockey',     'name' => ['en' => 'Ice Hockey', 'uk' => 'Хокей',        'ru' => 'Хоккей',        'es' => 'Hockey'],     'has_competitions' => false, 'sort_order' => 3],
        ];

        foreach ($sports as $s) {
            Sport::updateOrCreate(['slug' => $s['slug']], $s + ['is_active' => true]);
        }
    }
}
