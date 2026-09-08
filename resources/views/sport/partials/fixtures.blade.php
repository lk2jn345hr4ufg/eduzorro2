@php
    $allFixtures   = $allFixtures ?? [];
    $seasons       = $seasons ?? [];
    $currentSeason = $currentSeason ?? null;

    // Flatten to the minimum the calendar needs, so the JSON payload stays small.
    $calendarData = collect($allFixtures)->map(function ($fx) use ($team) {
        $date   = data_get($fx, 'fixture.date');
        $homeId = (int) data_get($fx, 'teams.home.id');
        $status = data_get($fx, 'fixture.status.short');

        return [
            'date'   => $date ? substr($date, 0, 10) : null,
            'time'   => $date ? substr($date, 11, 5) : null,
            'home'   => data_get($fx, 'teams.home.name'),
            'away'   => data_get($fx, 'teams.away.name'),
            'hg'     => data_get($fx, 'goals.home'),
            'ag'     => data_get($fx, 'goals.away'),
            'comp'   => data_get($fx, 'league.name'),
            'venue'  => $homeId === (int) $team->api_id ? 'H' : 'A',
            'played' => in_array($status, ['FT', 'AET', 'PEN'], true),
        ];
    })->filter(fn ($m) => $m['date'])->sortBy('date')->values();
@endphp

@if ($calendarData->isEmpty())
    <p>{{ __('sport.no_fixtures') }}</p>
@else
    <div class="fixtures-tool" id="fixtures-tool">
        <div class="fixtures-bar">
            @if ($currentSeason)
                <span class="fixtures-season-label">
                    {{ __('sport.season') }}: {{ $currentSeason }}/{{ substr((string) ($currentSeason + 1), -2) }}
                </span>
            @endif

            <div class="fixtures-filters">
                <button type="button" class="btn-filter is-active" data-filter="all">{{ __('sport.all_matches') }}</button>
                <button type="button" class="btn-filter" data-filter="upcoming">{{ __('sport.upcoming') }}</button>
                <button type="button" class="btn-filter" data-filter="played">{{ __('sport.results') }}</button>
            </div>
        </div>

        <div class="fixtures-layout">
            <div class="calendar">
                <div class="calendar-head">
                    <button type="button" class="cal-nav" id="cal-prev" aria-label="&larr;">&lsaquo;</button>
                    <strong id="cal-title"></strong>
                    <button type="button" class="cal-nav" id="cal-next" aria-label="&rarr;">&rsaquo;</button>
                </div>
                <div class="calendar-weekdays" id="cal-weekdays"></div>
                <div class="calendar-grid" id="cal-grid"></div>
                <p class="calendar-hint">{{ __('sport.calendar_hint') }}</p>
            </div>

            <div class="fixtures-list" id="fx-list"></div>
        </div>
    </div>

    <script>
    (function () {
        var MATCHES = @json($calendarData);
        if (!MATCHES.length) return;

        var locale = @json(str_replace('_', '-', app()->getLocale()));
        var noneText = @json(__('sport.no_fixtures'));

        var grid = document.getElementById('cal-grid');
        var title = document.getElementById('cal-title');
        var list = document.getElementById('fx-list');
        var weekdaysEl = document.getElementById('cal-weekdays');

        var byDate = {};
        MATCHES.forEach(function (m) { (byDate[m.date] = byDate[m.date] || []).push(m); });

        var filter = 'all';
        var selectedDay = null;
        var today = new Date().toISOString().slice(0, 10);

        // Open on the month of the next upcoming match, else the last played one.
        var anchor = MATCHES.filter(function (m) { return m.date >= today; })[0] || MATCHES[MATCHES.length - 1];
        var view = new Date(anchor.date + 'T00:00:00');
        view.setDate(1);

        // Monday-first weekday headers, localised (2024-01-01 was a Monday).
        var wd = [];
        for (var i = 0; i < 7; i++) {
            wd.push(new Date(Date.UTC(2024, 0, 1 + i)).toLocaleDateString(locale, { weekday: 'short' }));
        }
        weekdaysEl.innerHTML = wd.map(function (w) { return '<span>' + w + '</span>'; }).join('');

        function pass(m) {
            return filter === 'all' || (filter === 'played' ? m.played : !m.played);
        }

        function pad(n) { return (n < 10 ? '0' : '') + n; }

        function iso(y, mo, da) { return y + '-' + pad(mo + 1) + '-' + pad(da); }

        function esc(s) {
            return String(s === null || s === undefined ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }

        function renderCalendar() {
            var y = view.getFullYear(), mo = view.getMonth();
            title.textContent = view.toLocaleDateString(locale, { month: 'long', year: 'numeric' });

            var startOffset = (new Date(y, mo, 1).getDay() + 6) % 7; // Monday = 0
            var daysInMonth = new Date(y, mo + 1, 0).getDate();
            var cells = '';

            for (var b = 0; b < startOffset; b++) cells += '<span class="cal-cell is-empty"></span>';

            for (var d = 1; d <= daysInMonth; d++) {
                var key = iso(y, mo, d);
                var games = (byDate[key] || []).filter(pass);
                var cls = 'cal-cell';

                if (games.length) {
                    cls += games.some(function (g) { return g.played; }) ? ' has-played' : ' has-upcoming';
                }
                if (key === selectedDay) cls += ' is-selected';
                if (key === today) cls += ' is-today';

                cells += '<button type="button" class="' + cls + '" data-day="' + key + '"' +
                         (games.length ? '' : ' disabled') + '>' + d +
                         (games.length ? '<i class="cal-dot"></i>' : '') + '</button>';
            }

            grid.innerHTML = cells;

            Array.prototype.forEach.call(grid.querySelectorAll('.cal-cell[data-day]:not([disabled])'), function (el) {
                el.addEventListener('click', function () {
                    selectedDay = (selectedDay === el.dataset.day) ? null : el.dataset.day;
                    renderCalendar();
                    renderList();
                });
            });
        }

        function row(m) {
            var score = m.played
                ? '<strong>' + (m.hg === null ? '-' : m.hg) + ':' + (m.ag === null ? '-' : m.ag) + '</strong>'
                : '<span class="fx-time">' + esc(m.time) + '</span>';

            var when = new Date(m.date + 'T00:00:00')
                .toLocaleDateString(locale, { day: 'numeric', month: 'short' });

            return '<li class="fx-row' + (m.played ? '' : ' is-upcoming') + '">' +
                     '<span class="fx-date">' + when + '</span>' +
                     '<span class="fx-badge">' + m.venue + '</span>' +
                     '<span class="fx-teams">' + esc(m.home) + ' &mdash; ' + esc(m.away) + '</span>' +
                     '<span class="fx-score">' + score + '</span>' +
                     '<span class="fx-comp">' + esc(m.comp) + '</span>' +
                   '</li>';
        }

        function renderList() {
            var items = MATCHES.filter(pass);
            if (selectedDay) {
                items = items.filter(function (m) { return m.date === selectedDay; });
            }

            if (!items.length) {
                list.innerHTML = '<p class="fx-empty">' + esc(noneText) + '</p>';
                return;
            }

            if (filter === 'played') items = items.slice().reverse();

            var heading = selectedDay
                ? new Date(selectedDay + 'T00:00:00').toLocaleDateString(locale, { day: 'numeric', month: 'long', year: 'numeric' })
                : '';

            list.innerHTML = (heading ? '<h3 class="fx-heading">' + heading + '</h3>' : '') +
                             '<ul class="fx-list">' + items.map(row).join('') + '</ul>';
        }

        document.getElementById('cal-prev').addEventListener('click', function () {
            view.setMonth(view.getMonth() - 1); renderCalendar();
        });
        document.getElementById('cal-next').addEventListener('click', function () {
            view.setMonth(view.getMonth() + 1); renderCalendar();
        });

        Array.prototype.forEach.call(document.querySelectorAll('.btn-filter'), function (btn) {
            btn.addEventListener('click', function () {
                Array.prototype.forEach.call(document.querySelectorAll('.btn-filter'), function (b) {
                    b.classList.remove('is-active');
                });
                btn.classList.add('is-active');
                filter = btn.dataset.filter;
                selectedDay = null;
                renderCalendar();
                renderList();
            });
        });

        renderCalendar();
        renderList();
    })();
    </script>
@endif
