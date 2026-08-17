<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;

/**
 * Overlays admin-managed settings (stored in the `settings` table) onto the
 * football/news config at runtime. Every existing config('football...') /
 * config('news...') read then transparently picks up the admin value, with the
 * .env/config default used whenever a setting is empty or unset.
 *
 * Guarded so it never breaks migrate/boot before the table exists.
 */
class SettingsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Maps a settings key => config path. Only non-empty values override.
        $map = [
            'api_football_key'    => 'football.api.key',
            'api_football_season' => 'football.season',
            'news_provider'       => 'news.provider',
            'news_api_key'        => 'news.key',
            'news_language'       => 'news.language',
            'news_query_suffix'   => 'news.query_suffix',
            'news_per_team'       => 'news.per_team',
            'gemini_api_key'      => 'gemini.key',
            'gemini_model'        => 'gemini.model',
            'gemini_news_prompt'  => 'gemini.prompt',
            'gemini_enabled'      => 'gemini.enabled',
        ];

        try {
            $settings = Setting::allValues();
        } catch (\Throwable) {
            return; // DB not ready (e.g. during initial migrate)
        }

        foreach ($map as $settingKey => $configPath) {
            $value = $settings[$settingKey] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            // Cast the couple of integer-ish settings.
            if (in_array($settingKey, ['api_football_season', 'news_per_team'], true)) {
                $value = (int) $value;
            }

            // Boolean toggle stored as '1'/'0'.
            if ($settingKey === 'gemini_enabled') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            config([$configPath => $value]);
        }
    }
}
