<?php

namespace Database\Seeders;

use App\Models\Tool;
use Illuminate\Database\Seeder;

/**
 * Video & lecture study tools. These help people work WITH lecture videos
 * (linking to a moment, planning a course, organising their own notes) rather
 * than copying the videos themselves.
 */
class VideoToolSeeder extends Seeder
{
    public function run(): void
    {
        $t = fn (string $en, string $uk, string $ru, string $es) => compact('en', 'uk', 'ru', 'es');

        $tools = [
            'youtube-timestamp' => [
                $t('Timestamp link & embed generator', 'Генератор таймкод-посилань і вставки',
                   'Генератор таймкод-ссылок и вставки', 'Generador de enlaces con marca de tiempo'),
                $t('Link a lecture at an exact moment and get the embed code for your page.',
                   'Посилайтеся на конкретну секунду лекції та отримайте код для вставки на сторінку.',
                   'Ссылайтесь на конкретную секунду лекции и получите код для вставки на страницу.',
                   'Enlaza una clase en un momento exacto y obtén su código de inserción.'),
                0,
            ],
            'video-study-planner' => [
                $t('Video course planner', 'Планувальник відеокурсу',
                   'Планировщик видеокурса', 'Planificador de videocurso'),
                $t('Work out how many days a video course takes at your pace and playback speed.',
                   'Дізнайтеся, скільки днів займе відеокурс у вашому темпі та на вашій швидкості.',
                   'Узнайте, сколько дней займёт видеокурс в вашем темпе и на вашей скорости.',
                   'Calcula cuántos días te llevará un videocurso a tu ritmo.'),
                1,
            ],
            'video-notes-ai' => [
                $t('Lecture notes organiser (AI)', 'Впорядкування конспекту (ШІ)',
                   'Упорядочивание конспекта (ИИ)', 'Organizador de apuntes (IA)'),
                $t('Turn your own lecture notes into an outline, self-check questions or a revision plan.',
                   'Перетворіть власний конспект на структурований план, питання для самоперевірки або графік повторення.',
                   'Превратите собственный конспект в структурированный план, вопросы для самопроверки или график повторения.',
                   'Convierte tus apuntes en un esquema, preguntas o un plan de repaso.'),
                2,
            ],
            'offline-video-guide' => [
                $t('Watching lectures offline', 'Як дивитися лекції офлайн',
                   'Как смотреть лекции офлайн', 'Ver clases sin conexión'),
                $t('Legal ways to keep lectures available without an internet connection.',
                   'Легальні способи мати лекції під рукою без інтернету.',
                   'Легальные способы иметь лекции под рукой без интернета.',
                   'Formas legales de tener las clases disponibles sin conexión.'),
                3,
            ],
        ];

        foreach ($tools as $slug => [$name, $description, $order]) {
            Tool::updateOrCreate(
                ['slug' => $slug],
                [
                    'category'    => 'video',
                    'name'        => $name,
                    'description' => $description,
                    'sort_order'  => 300 + $order,
                    'is_active'   => true,
                ]
            );
        }
    }
}
