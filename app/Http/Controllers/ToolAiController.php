<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Services\AI\GeminiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Backend for the AI-assisted study tools.
 *
 * The tool takes notes the visitor wrote themselves and restructures them; it
 * never fetches or processes third-party video content.
 */
class ToolAiController extends Controller
{
    public function videoNotes(Request $request, Language $language, GeminiClient $gemini): JsonResponse
    {
        $data = $request->validate([
            'notes' => ['required', 'string', 'min:40', 'max:8000'],
            'mode'  => ['nullable', 'in:summary,questions,plan'],
        ]);

        // Cheap abuse guard: this endpoint costs a Gemini call per request.
        $key = 'video-notes:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json([
                'error' => __('tools.rate_limited', ['seconds' => RateLimiter::availableIn($key)]),
            ], 429);
        }

        RateLimiter::hit($key, 3600);

        if (! $gemini->isConfigured()) {
            return response()->json(['error' => __('tools.ai_unavailable')], 503);
        }

        $result = $gemini->complete($this->prompt($data['notes'], $data['mode'] ?? 'summary', $language->code));

        if (! $result) {
            return response()->json(['error' => __('tools.ai_failed')], 502);
        }

        return response()->json(['result' => $result]);
    }

    protected function prompt(string $notes, string $mode, string $locale): string
    {
        $instruction = match ($mode) {
            'questions' => 'Turn the notes into 8-12 self-check questions with short answers, grouped by topic.',
            'plan'      => 'Turn the notes into a spaced-repetition study plan: what to review on day 1, 3, 7 and 30, with the specific points to revisit each time.',
            default     => 'Restructure the notes into a clear outline: a one-paragraph summary, then the key points as nested bullets, then any terms worth memorising with brief definitions.',
        };

        return "You are helping a student organise their own lecture notes.\n"
            .$instruction."\n"
            ."Work only from the notes provided — do not invent facts that are not there.\n"
            ."Answer in the language with ISO code \"{$locale}\". Use plain text with simple dashes for bullets, no markdown headers.\n\n"
            ."NOTES:\n".$notes;
    }
}
