@php($preview = $preview ?? null)

@if (! $preview)
    <div class="text-sm text-gray-500 dark:text-gray-400">
        No preview available. Check that the Gemini key is set (Settings → Gemini)
        and that the article has a title, then try again.
    </div>
@else
    <div class="space-y-6 max-h-[60vh] overflow-y-auto pr-2">
        @foreach ($locales as $code)
            <div class="rounded-lg border border-gray-200 dark:border-white/10 p-4">
                <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                    {{ strtoupper($code) }}
                </div>

                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-gray-500 dark:text-gray-400">Title</div>
                        <div class="font-medium">{{ data_get($preview, "title.$code", '—') }}</div>
                    </div>

                    @if (data_get($preview, "excerpt.$code"))
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Excerpt</div>
                            <div>{{ data_get($preview, "excerpt.$code") }}</div>
                        </div>
                    @endif

                    @if (data_get($preview, "body.$code"))
                        <div>
                            <div class="text-gray-500 dark:text-gray-400">Body</div>
                            <div class="whitespace-pre-line">{{ data_get($preview, "body.$code") }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
        “Apply to form” fills the fields below — nothing is saved until you press Save.
    </p>
@endif
