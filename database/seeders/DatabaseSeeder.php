<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Language;
use App\Models\Region;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Admin user (Filament panel login) ------------------------------
        User::firstOrCreate(
            ['email' => '[email protected]'],
            ['name' => 'Eduzorro Admin', 'password' => Hash::make('password')]
        );

        // ---- Languages ------------------------------------------------------
        $en = Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'sort_order' => 1]);
        $es = Language::create(['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'sort_order' => 2]);

        // ---- Regions --------------------------------------------------------
        $global = Region::create([
            'slug' => 'global', 'code' => 'GLOBAL', 'sort_order' => 0,
            'name' => ['en' => 'Global (Online)', 'es' => 'Global (En línea)'],
        ]);
        $us = Region::create([
            'slug' => 'united-states', 'code' => 'US', 'sort_order' => 1,
            'latitude' => 39.8283, 'longitude' => -98.5795,
            'name' => ['en' => 'United States', 'es' => 'Estados Unidos'],
        ]);
        $es_region = Region::create([
            'slug' => 'spain', 'code' => 'ES', 'sort_order' => 2,
            'latitude' => 40.4168, 'longitude' => -3.7038,
            'name' => ['en' => 'Spain', 'es' => 'España'],
        ]);

        // ---- Industries & categories ---------------------------------------
        $blueprint = [
            'language-learning' => [
                'name' => ['en' => 'Language Learning', 'es' => 'Aprendizaje de idiomas'],
                'categories' => [
                    'language-schools' => ['en' => 'Language Schools', 'es' => 'Escuelas de idiomas'],
                    'online-language-apps' => ['en' => 'Online Language Apps', 'es' => 'Apps de idiomas en línea'],
                    'private-tutors' => ['en' => 'Private Tutors', 'es' => 'Profesores particulares'],
                ],
            ],
            'test-prep' => [
                'name' => ['en' => 'Test Preparation', 'es' => 'Preparación de exámenes'],
                'categories' => [
                    'university-entrance' => ['en' => 'University Entrance', 'es' => 'Acceso a la universidad'],
                    'professional-certification' => ['en' => 'Professional Certification', 'es' => 'Certificación profesional'],
                ],
            ],
            'higher-education' => [
                'name' => ['en' => 'Higher Education', 'es' => 'Educación superior'],
                'categories' => [
                    'universities' => ['en' => 'Universities', 'es' => 'Universidades'],
                    'online-degrees' => ['en' => 'Online Degrees', 'es' => 'Titulaciones en línea'],
                ],
            ],
            'skills-and-training' => [
                'name' => ['en' => 'Skills & Training', 'es' => 'Habilidades y formación'],
                'categories' => [
                    'coding-bootcamps' => ['en' => 'Coding Bootcamps', 'es' => 'Bootcamps de programación'],
                    'business-courses' => ['en' => 'Business Courses', 'es' => 'Cursos de negocios'],
                ],
            ],
        ];

        $categoryModels = [];
        $order = 0;
        foreach ($blueprint as $iSlug => $iData) {
            $industry = Industry::create([
                'slug' => $iSlug,
                'name' => $iData['name'],
                'sort_order' => $order++,
            ]);
            $cOrder = 0;
            foreach ($iData['categories'] as $cSlug => $cName) {
                $categoryModels[$cSlug] = Category::create([
                    'industry_id' => $industry->id,
                    'slug' => $cSlug,
                    'name' => $cName,
                    'sort_order' => $cOrder++,
                ]);
            }
        }

        // ---- Companies ------------------------------------------------------
        $companies = [
            ['LinguaHub Madrid', 'language-schools', 'local', $es_region, 40.4168, -3.7038, 'Calle Gran Vía 28, Madrid'],
            ['NYC English Center', 'language-schools', 'local', $us, 40.7128, -74.0060, '350 5th Ave, New York, NY'],
            ['FluentLoop', 'online-language-apps', 'digital', $global, null, null, null],
            ['TutorBridge', 'private-tutors', 'digital', $global, null, null, null],
            ['ExamAce Prep', 'university-entrance', 'local', $us, 34.0522, -118.2437, '600 W 5th St, Los Angeles, CA'],
            ['CertifyPro', 'professional-certification', 'digital', $global, null, null, null],
            ['Universidad Iberia', 'universities', 'local', $es_region, 41.3874, 2.1686, 'Av. Diagonal 500, Barcelona'],
            ['OpenDegree Online', 'online-degrees', 'digital', $global, null, null, null],
            ['CodeForge Bootcamp', 'coding-bootcamps', 'local', $us, 37.7749, -122.4194, '1 Market St, San Francisco, CA'],
            ['BizSkills Academy', 'business-courses', 'digital', $global, null, null, null],
        ];

        foreach ($companies as [$name, $catSlug, $type, $region, $lat, $lng, $address]) {
            $company = Company::create([
                'category_id' => $categoryModels[$catSlug]->id,
                'slug'        => Str::slug($name),
                'name'        => $name,
                'type'        => $type,
                'description' => [
                    'en' => "{$name} is a trusted provider in education, helping students reach their goals with proven programs and experienced instructors.",
                    'es' => "{$name} es un proveedor educativo de confianza que ayuda a los estudiantes a alcanzar sus metas con programas probados e instructores con experiencia.",
                ],
                'address'   => $address,
                'latitude'  => $lat,
                'longitude' => $lng,
                'email'     => 'hello@' . Str::slug($name) . '.example',
                'phone'     => '+1 555 0100',
                'website'   => 'https://' . Str::slug($name) . '.example',
                'is_verified' => $type === 'local',
            ]);

            // Local companies -> their region; digital -> all active regions.
            $company->regions()->attach(
                $type === 'digital'
                    ? Region::active()->pluck('id')
                    : [$region->id]
            );

            // Seed a few approved reviews.
            foreach (range(1, rand(2, 5)) as $i) {
                Review::create([
                    'company_id'  => $company->id,
                    'author_name' => 'Reviewer ' . $i,
                    'rating'      => rand(3, 5),
                    'title'       => 'Great experience',
                    'body'        => 'Helpful staff, clear structure, and good results overall. Would recommend to others.',
                    'is_approved' => true,
                ]);
            }
        }
    }
}
