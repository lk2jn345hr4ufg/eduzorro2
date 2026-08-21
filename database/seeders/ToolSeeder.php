<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $tools = [
            [
                'slug'     => 'gpa-calculator',
                'category' => 'grades',
                'name' => [
                    'en' => 'GPA calculator',
                    'uk' => 'Калькулятор GPA',
                    'ru' => 'Калькулятор GPA',
                    'es' => 'Calculadora de GPA',
                ],
                'description' => [
                    'en' => 'Work out your weighted grade point average on the 4.0 scale.',
                    'uk' => 'Обчисліть зважений середній бал (GPA) за шкалою 4.0.',
                    'ru' => 'Рассчитайте взвешенный средний балл (GPA) по шкале 4.0.',
                    'es' => 'Calcula tu promedio ponderado (GPA) en la escala 4.0.',
                ],
                'sort_order' => 0,
            ],
            [
                'slug'     => 'grade-converter',
                'category' => 'grades',
                'name' => [
                    'en' => 'Grade converter',
                    'uk' => 'Конвертер оцінок',
                    'ru' => 'Конвертер оценок',
                    'es' => 'Conversor de notas',
                ],
                'description' => [
                    'en' => 'Convert a grade between the US, UK, ECTS, Ukrainian 12-point and 5-point systems.',
                    'uk' => 'Переведіть оцінку між системами США, Великої Британії, ECTS, українською 12-бальною та 5-бальною.',
                    'ru' => 'Переведите оценку между системами США, Великобритании, ECTS, украинской 12-балльной и 5-балльной.',
                    'es' => 'Convierte una nota entre los sistemas de EE. UU., Reino Unido, ECTS y más.',
                ],
                'sort_order' => 1,
            ],
        ];

        foreach ($tools as $t) {
            Tool::updateOrCreate(['slug' => $t['slug']], $t + ['is_active' => true]);
        }
    }
}
