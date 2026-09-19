<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SeoController extends Controller
{
    /** Человеческие названия разделов для интерфейса. */
    private const LABELS = [
        'home'      => 'Главная / Новости',
        'posts'     => 'Статьи',
        'fixtures'  => 'Расписание',
        'euro'      => 'Еврокубки',
        'standings' => 'Турнирная таблица',
        'transfers' => 'Трансферы',
    ];

    public function edit()
    {
        $sections = [];

        foreach (config('seo', []) as $key => $default) {
            $sections[$key] = [
                'label'          => self::LABELS[$key] ?? $key,
                'title'          => Setting::get("seo.$key.title", '') ?: '',
                'description'    => Setting::get("seo.$key.description", '') ?: '',
                'defaultTitle'   => $default['title'] ?? '',
                'defaultDesc'    => $default['description'] ?? '',
            ];
        }

        return view('admin.seo', compact('sections'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title'       => 'array',
            'title.*'     => 'nullable|string|max:120',
            'description' => 'array',
            'description.*' => 'nullable|string|max:300',
        ]);

        foreach (array_keys(config('seo', [])) as $key) {
            // Пустое значение = вернуться к дефолту из config/seo.php.
            Setting::set("seo.$key.title", trim((string) data_get($data, "title.$key")));
            Setting::set("seo.$key.description", trim((string) data_get($data, "description.$key")));
        }

        return back()->with('ok', 'Мета-теги сохранены.');
    }
}
