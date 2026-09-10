<p class="tool-hint">{{ __('tools.notes_ai_hint') }}</p>

<div class="tool-panel" id="notes-ai">
    <div class="tool-field">
        <label for="na-notes">{{ __('tools.your_notes') }}</label>
        <textarea id="na-notes" class="inp" rows="10" placeholder="{{ __('tools.notes_placeholder') }}"></textarea>
        <p class="tool-note"><span id="na-count">0</span> / 8000</p>
    </div>

    <div class="tool-field">
        <label for="na-mode">{{ __('tools.what_to_make') }}</label>
        <select id="na-mode" class="inp">
            <option value="summary">{{ __('tools.mode_outline') }}</option>
            <option value="questions">{{ __('tools.mode_questions') }}</option>
            <option value="plan">{{ __('tools.mode_plan') }}</option>
        </select>
    </div>

    <div class="tool-actions">
        <button type="button" class="btn" id="na-go">{{ __('tools.generate') }}</button>
        <button type="button" class="btn btn-ghost" id="na-copy" hidden>{{ __('tools.copy') }}</button>
    </div>

    <p class="tool-note" id="na-status"></p>
    <pre class="tool-output" id="na-out" hidden></pre>
</div>

<script>
(function () {
    var notes = document.getElementById('na-notes');
    var mode = document.getElementById('na-mode');
    var go = document.getElementById('na-go');
    var copy = document.getElementById('na-copy');
    var out = document.getElementById('na-out');
    var status = document.getElementById('na-status');
    var counter = document.getElementById('na-count');

    var endpoint = @json(route('tools.video-notes.generate', [$currentLanguage]));
    var token = document.querySelector('meta[name="csrf-token"]');
    var strings = {
        working: @json(__('tools.working')),
        tooShort: @json(__('tools.notes_too_short')),
        failed: @json(__('tools.ai_failed'))
    };

    notes.addEventListener('input', function () {
        counter.textContent = notes.value.length;
    });

    go.addEventListener('click', function () {
        var text = notes.value.trim();

        if (text.length < 40) {
            status.textContent = strings.tooShort;
            return;
        }

        go.disabled = true;
        status.textContent = strings.working;
        out.hidden = true;
        copy.hidden = true;

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.getAttribute('content') : ''
            },
            body: JSON.stringify({ notes: text, mode: mode.value })
        })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
        .then(function (res) {
            if (!res.ok || !res.body.result) {
                status.textContent = res.body.error || strings.failed;
                return;
            }
            status.textContent = '';
            out.textContent = res.body.result;
            out.hidden = false;
            copy.hidden = false;
        })
        .catch(function () { status.textContent = strings.failed; })
        .then(function () { go.disabled = false; });
    });

    copy.addEventListener('click', function () {
        if (navigator.clipboard) { navigator.clipboard.writeText(out.textContent); }
        var old = copy.textContent;
        copy.textContent = '✓';
        setTimeout(function () { copy.textContent = old; }, 1200);
    });
})();
</script>
