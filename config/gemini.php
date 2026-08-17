<?php

return [
    'key'      => env('GEMINI_API_KEY'),
    'model'    => env('GEMINI_MODEL', 'gemini-2.0-flash'),
    'base_url' => rtrim(env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/'),
    'timeout'  => (int) env('GEMINI_TIMEOUT', 40),

    // When true, sport:sync-news rewrites & translates each article on import.
    'enabled'     => (bool) env('GEMINI_NEWS_REWRITE', false),
    'temperature' => (float) env('GEMINI_TEMPERATURE', 0.7),

    // Style/instructions for the rewrite. The article body and the strict JSON
    // output contract are appended by the code, so this only controls the
    // editorial "how". Editable from the admin (Settings → Gemini).
    'prompt' => 'You are an experienced football news editor. Rewrite the article completely in your own words so the result is original and not a copy of the source. Keep every fact accurate, keep it concise, clear and engaging, and never invent details that are not supported by the source. Then translate the rewritten title, excerpt and body into each requested language, keeping names of clubs and players in their commonly used form.',
];
