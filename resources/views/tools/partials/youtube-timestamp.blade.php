<p class="tool-hint">{{ __('tools.yt_timestamp_hint') }}</p>

<div class="tool-panel" id="yt-ts">
    <div class="tool-field">
        <label for="yt-url">{{ __('tools.video_url') }}</label>
        <input type="url" id="yt-url" class="inp" placeholder="https://www.youtube.com/watch?v=..." value="">
    </div>

    <div class="math-fields">
        <div class="tool-field">
            <label for="yt-h">{{ __('tools.hours') }}</label>
            <input type="number" id="yt-h" class="inp" min="0" step="1" value="0">
        </div>
        <div class="tool-field">
            <label for="yt-m">{{ __('tools.minutes') }}</label>
            <input type="number" id="yt-m" class="inp" min="0" max="59" step="1" value="1">
        </div>
        <div class="tool-field">
            <label for="yt-s">{{ __('tools.seconds') }}</label>
            <input type="number" id="yt-s" class="inp" min="0" max="59" step="1" value="30">
        </div>
    </div>

    <div class="tool-out-block">
        <label>{{ __('tools.share_link') }}</label>
        <div class="copy-row">
            <input type="text" id="yt-link" class="inp" readonly>
            <button type="button" class="btn" data-copy="yt-link">{{ __('tools.copy') }}</button>
        </div>
    </div>

    <div class="tool-out-block">
        <label>{{ __('tools.embed_code') }}</label>
        <div class="copy-row">
            <textarea id="yt-embed" class="inp" rows="3" readonly></textarea>
            <button type="button" class="btn" data-copy="yt-embed">{{ __('tools.copy') }}</button>
        </div>
    </div>

    <p class="tool-note" id="yt-error"></p>
</div>

<script>
(function () {
    var url = document.getElementById('yt-url');
    var h = document.getElementById('yt-h'), m = document.getElementById('yt-m'), s = document.getElementById('yt-s');
    var link = document.getElementById('yt-link'), embed = document.getElementById('yt-embed');
    var err = document.getElementById('yt-error');
    var invalid = @json(__('tools.invalid_video_url'));

    // Accepts watch?v=, youtu.be/, /embed/ and /shorts/ forms.
    function videoId(value) {
        var v = String(value || '').trim();
        if (!v) return null;

        var m1 = v.match(/[?&]v=([A-Za-z0-9_-]{11})/);
        if (m1) return m1[1];

        var m2 = v.match(/(?:youtu\.be\/|\/embed\/|\/shorts\/|\/live\/)([A-Za-z0-9_-]{11})/);
        if (m2) return m2[1];

        if (/^[A-Za-z0-9_-]{11}$/.test(v)) return v;

        return null;
    }

    function seconds() {
        var t = (parseInt(h.value, 10) || 0) * 3600 +
                (parseInt(m.value, 10) || 0) * 60 +
                (parseInt(s.value, 10) || 0);
        return t > 0 ? t : 0;
    }

    function update() {
        var id = videoId(url.value);

        if (!id) {
            link.value = '';
            embed.value = '';
            err.textContent = url.value.trim() ? invalid : '';
            return;
        }

        err.textContent = '';
        var t = seconds();

        link.value = 'https://youtu.be/' + id + (t ? '?t=' + t : '');
        embed.value = '<iframe width="560" height="315" src="https://www.youtube.com/embed/' + id +
                      (t ? '?start=' + t : '') +
                      '" title="YouTube video player" frameborder="0" ' +
                      'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" ' +
                      'allowfullscreen></iframe>';
    }

    [url, h, m, s].forEach(function (el) { el.addEventListener('input', update); });

    Array.prototype.forEach.call(document.querySelectorAll('[data-copy]'), function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.dataset.copy);
            if (!target.value) return;
            target.select();
            try { document.execCommand('copy'); } catch (e) {}
            if (navigator.clipboard) { navigator.clipboard.writeText(target.value); }
            var old = btn.textContent;
            btn.textContent = '✓';
            setTimeout(function () { btn.textContent = old; }, 1200);
        });
    });

    update();
})();
</script>
