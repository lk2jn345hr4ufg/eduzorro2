<?php

namespace App\Support;

/**
 * Renders rich-text fields (admin RichEditor output, or HTML imported from
 * WordPress) safely-ish: allowlisted tags only, event handlers and
 * javascript: URLs stripped. Plain text (no tags) gets nl2br treatment so
 * imported newline-formatted descriptions still look right.
 *
 * Content here comes from the site's own admins and its own WordPress
 * export — trusted-adjacent, not user-submitted. If untrusted users ever
 * gain write access to these fields, swap this for a real sanitizer
 * (e.g. mews/purifier).
 */
class Sanitize
{
    private const ALLOWED_INLINE = '<strong><b><em><i><u><span><a><br>';

    private const ALLOWED_BLOCK = '<p><br><a><strong><b><em><i><u><span><ul><ol><li><h2><h3><h4><blockquote><hr>';

    /** For multi-line rich fields: description, details_description. */
    public static function rich(?string $value): string
    {
        return self::clean($value, self::ALLOWED_BLOCK, nl2brPlain: true);
    }

    /** For single-line fields that may carry inline markup: description_title. */
    public static function inline(?string $value): string
    {
        return self::clean($value, self::ALLOWED_INLINE, nl2brPlain: false);
    }

    private static function clean(?string $value, string $allowedTags, bool $nl2brPlain): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        // Plain text (no markup at all): escape and preserve line breaks.
        if (! str_contains($value, '<')) {
            return $nl2brPlain ? nl2br(e($value)) : e($value);
        }

        $value = strip_tags($value, $allowedTags);

        // Strip inline event handlers (onclick=...) and javascript: URLs.
        $value = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value);
        $value = preg_replace('/\s(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2/i', '', $value);

        return $value;
    }
}
