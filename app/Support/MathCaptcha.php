<?php

namespace App\Support;

/**
 * Session-backed arithmetic captcha. No external service, no API keys —
 * appropriate for blocking the dumb comment-spam bots that actually hit
 * review forms. Paired with a honeypot field in the form for bots that
 * blindly fill every input.
 */
class MathCaptcha
{
    private const SESSION_KEY = 'math_captcha_answer';

    /** Generate a fresh question and remember its answer in the session. */
    public static function question(): string
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);

        session([self::SESSION_KEY => $a + $b]);

        return "{$a} + {$b}";
    }

    /** One-shot check: the stored answer is consumed whether right or wrong. */
    public static function check(mixed $value): bool
    {
        $expected = session(self::SESSION_KEY);
        session()->forget(self::SESSION_KEY);

        return $expected !== null && (int) $value === (int) $expected;
    }
}
