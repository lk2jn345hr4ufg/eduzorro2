<p class="tool-hint">{{ __('tools.planner_hint') }}</p>

<div class="tool-panel" id="yt-planner">
    <div class="tool-field">
        <label for="pl-list">{{ __('tools.durations') }}</label>
        <textarea id="pl-list" class="inp" rows="6" placeholder="12:30&#10;8:05&#10;1:04:20">12:30
8:05
21:47
1:04:20
15:00</textarea>
    </div>

    <div class="math-fields">
        <div class="tool-field">
            <label for="pl-daily">{{ __('tools.minutes_per_day') }}</label>
            <input type="number" id="pl-daily" class="inp" min="1" step="5" value="45">
        </div>
        <div class="tool-field">
            <label for="pl-speed">{{ __('tools.playback_speed') }}</label>
            <select id="pl-speed" class="inp">
                <option value="1">1×</option>
                <option value="1.25">1.25×</option>
                <option value="1.5">1.5×</option>
                <option value="1.75">1.75×</option>
                <option value="2">2×</option>
            </select>
        </div>
        <div class="tool-field">
            <label for="pl-notes">{{ __('tools.notes_overhead') }}</label>
            <input type="number" id="pl-notes" class="inp" min="0" max="200" step="10" value="30">
        </div>
    </div>

    <table class="tool-table tool-table-result">
        <tbody>
            <tr><td>{{ __('tools.videos_count') }}</td><td><strong id="pl-count">—</strong></td></tr>
            <tr><td>{{ __('tools.total_length') }}</td><td><strong id="pl-total">—</strong></td></tr>
            <tr><td>{{ __('tools.at_speed') }}</td><td><strong id="pl-adjusted">—</strong></td></tr>
            <tr><td>{{ __('tools.with_notes') }}</td><td><strong id="pl-effort">—</strong></td></tr>
            <tr><td>{{ __('tools.days_needed') }}</td><td><strong id="pl-days">—</strong></td></tr>
            <tr><td>{{ __('tools.finish_by') }}</td><td><strong id="pl-finish">—</strong></td></tr>
        </tbody>
    </table>
</div>

<script>
(function () {
    var list = document.getElementById('pl-list');
    var daily = document.getElementById('pl-daily');
    var speed = document.getElementById('pl-speed');
    var notes = document.getElementById('pl-notes');
    var locale = @json(str_replace('_', '-', app()->getLocale()));

    // Accepts "12:30", "1:04:20", "755" (seconds) or "12m 30s" per line.
    function parseLine(line) {
        var v = String(line).trim();
        if (!v) return 0;

        if (/^\d+(\.\d+)?$/.test(v)) return Math.round(parseFloat(v) * 60);

        var hms = v.match(/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/);
        if (hms) {
            return hms[3]
                ? (+hms[1]) * 3600 + (+hms[2]) * 60 + (+hms[3])
                : (+hms[1]) * 60 + (+hms[2]);
        }

        var loose = 0, mm;
        if ((mm = v.match(/(\d+)\s*h/i))) loose += (+mm[1]) * 3600;
        if ((mm = v.match(/(\d+)\s*m/i))) loose += (+mm[1]) * 60;
        if ((mm = v.match(/(\d+)\s*s/i))) loose += (+mm[1]);

        return loose;
    }

    function fmt(sec) {
        sec = Math.round(sec);
        var h = Math.floor(sec / 3600), m = Math.round((sec % 3600) / 60);
        if (m === 60) { h += 1; m = 0; }
        return h ? h + ' h ' + m + ' min' : m + ' min';
    }

    function update() {
        var items = list.value.split(/\n+/).map(parseLine).filter(function (n) { return n > 0; });
        var count = items.length;

        if (!count) {
            ['pl-count','pl-total','pl-adjusted','pl-effort','pl-days','pl-finish'].forEach(function (id) {
                document.getElementById(id).textContent = '—';
            });
            return;
        }

        var total = items.reduce(function (a, b) { return a + b; }, 0);
        var sp = parseFloat(speed.value) || 1;
        var adjusted = total / sp;
        var effort = adjusted * (1 + (parseFloat(notes.value) || 0) / 100);

        var perDay = (parseFloat(daily.value) || 0) * 60;
        var days = perDay > 0 ? Math.ceil(effort / perDay) : null;

        document.getElementById('pl-count').textContent = count;
        document.getElementById('pl-total').textContent = fmt(total);
        document.getElementById('pl-adjusted').textContent = fmt(adjusted);
        document.getElementById('pl-effort').textContent = fmt(effort);
        document.getElementById('pl-days').textContent = days ? days : '—';

        if (days) {
            var d = new Date();
            d.setDate(d.getDate() + days - 1);
            document.getElementById('pl-finish').textContent =
                d.toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' });
        } else {
            document.getElementById('pl-finish').textContent = '—';
        }
    }

    [list, daily, speed, notes].forEach(function (el) {
        el.addEventListener('input', update);
        el.addEventListener('change', update);
    });

    update();
})();
</script>
