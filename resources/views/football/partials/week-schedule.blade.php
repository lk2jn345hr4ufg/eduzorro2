{{--
    Next 7 days, every competition.
    $days         date => (competition code => Fixtures)
    $competitions code => Competition
    $links        api id => team slug

    Without JS every day is listed one after another; with JS (below) it
    becomes a day strip plus competition filter chips.
--}}
@php
    $todayKey    = now()->toDateString();
    $tomorrowKey = now()->addDay()->toDateString();
    $usedCodes   = $days->flatMap(fn ($groups) => $groups->keys())->unique()
        ->sortBy(fn ($code) => $competitions->keys()->search($code))->values();
    $total       = $days->sum(fn ($groups) => $groups->flatten()->count());
    $firstDay    = $days->search(fn ($groups) => $groups->isNotEmpty()) ?: $todayKey;
    $hasOdds     = $days->flatten()->contains(fn ($f) => $f->odd);
@endphp

@if ($total === 0)
    <p class="empty">{{ __('football.no_fixtures') }}</p>
@else
    <div class="week" data-week data-first="{{ $firstDay }}">
        <div class="week-days" role="tablist" hidden>
            @foreach ($days as $date => $groups)
                @php($d = \Illuminate\Support\Carbon::parse($date))
                @php($n = $groups->flatten()->count())
                <button type="button" class="week-day-btn" role="tab" data-day="{{ $date }}" @disabled($n === 0)>
                    <span class="wd-name">
                        @if ($date === $todayKey) {{ __('football.today') }}
                        @elseif ($date === $tomorrowKey) {{ __('football.tomorrow') }}
                        @else {{ $d->translatedFormat('D') }}
                        @endif
                    </span>
                    <span class="wd-date">{{ $d->translatedFormat('j M') }}</span>
                    <span class="wd-count">{{ $n ?: '—' }}</span>
                </button>
            @endforeach
        </div>

        @if ($usedCodes->count() > 1)
            <div class="week-filter" hidden>
                <button type="button" class="chip is-active" data-comp="">{{ __('football.all_competitions') }}</button>
                @foreach ($usedCodes as $code)
                    @php($c = $competitions->get($code))
                    <button type="button" class="chip" data-comp="{{ $code }}">
                        @if ($c?->flag)<span class="flag" aria-hidden="true">{{ $c->flag }}</span>@endif
                        {{ $c ? $c->translate('name') : $code }}
                    </button>
                @endforeach
            </div>
        @endif

        @foreach ($days as $date => $groups)
            @continue($groups->isEmpty())
            @php($d = \Illuminate\Support\Carbon::parse($date))
            <section class="week-panel" data-day="{{ $date }}">
                <h3 class="match-day-head week-panel-head">
                    @if ($date === $todayKey)
                        <span class="day-chip">{{ __('football.today') }}</span>
                    @elseif ($date === $tomorrowKey)
                        <span class="day-chip">{{ __('football.tomorrow') }}</span>
                    @endif
                    {{ $d->translatedFormat('l, j F') }}
                </h3>

                @foreach ($groups as $code => $matches)
                    @php($c = $competitions->get($code))
                    <div class="week-comp" data-comp="{{ $code }}">
                        <div class="week-comp-head">
                            @if ($c?->emblem_url)
                                <img src="{{ $c->emblem_url }}" alt="" width="20" height="20" loading="lazy">
                            @endif
                            @if ($c && $c->is_active)
                                <a href="{{ route('competition.tab', [$currentLanguage, $c, 'fixtures']) }}">{{ $c->translate('name') }}</a>
                            @else
                                <span>{{ $matches->first()->league_name ?: $code }}</span>
                            @endif
                            @if ($c?->flag)<span class="flag" aria-hidden="true">{{ $c->flag }}</span>@endif
                            <span class="week-comp-count">{{ $matches->count() }}</span>
                        </div>
                        <div class="match-list">
                            @foreach ($matches as $f)
                                @include('football.partials.match-row', ['f' => $f])
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        @endforeach

        <p class="week-empty empty" hidden>{{ __('football.no_matches_filter') }}</p>

        @if ($hasOdds)
            <p class="odds-note">{{ __('football.odds_note') }}</p>
        @endif
    </div>

    @push('scripts')
    <script>
    (function () {
        var root = document.querySelector('[data-week]');
        if (!root) return;
        var dayBtns = root.querySelectorAll('.week-day-btn');
        var chips = root.querySelectorAll('.week-filter .chip');
        var panels = root.querySelectorAll('.week-panel');
        var empty = root.querySelector('.week-empty');
        var state = { day: root.getAttribute('data-first'), comp: '' };

        root.querySelector('.week-days').hidden = false;
        var filter = root.querySelector('.week-filter');
        if (filter) filter.hidden = false;
        root.classList.add('is-js');

        function render() {
            var visible = 0;
            panels.forEach(function (p) {
                var onDay = p.getAttribute('data-day') === state.day;
                var shown = 0;
                p.querySelectorAll('.week-comp').forEach(function (g) {
                    var ok = !state.comp || g.getAttribute('data-comp') === state.comp;
                    g.hidden = !ok;
                    if (ok) shown++;
                });
                p.hidden = !onDay || shown === 0;
                if (!p.hidden) visible++;
            });
            dayBtns.forEach(function (b) {
                var active = b.getAttribute('data-day') === state.day;
                b.classList.toggle('is-active', active);
                b.setAttribute('aria-selected', active ? 'true' : 'false');
                // Count per day respects the competition filter.
                var panel = root.querySelector('.week-panel[data-day="' + b.getAttribute('data-day') + '"]');
                var n = 0;
                if (panel) panel.querySelectorAll('.week-comp').forEach(function (g) {
                    if (!state.comp || g.getAttribute('data-comp') === state.comp) n += g.querySelectorAll('.match').length;
                });
                b.querySelector('.wd-count').textContent = n || '—';
                b.classList.toggle('is-empty', n === 0);
            });
            chips.forEach(function (c) { c.classList.toggle('is-active', c.getAttribute('data-comp') === state.comp); });
            empty.hidden = visible > 0;
        }

        dayBtns.forEach(function (b) {
            b.addEventListener('click', function () { if (!b.disabled) { state.day = b.getAttribute('data-day'); render(); } });
        });
        chips.forEach(function (c) {
            c.addEventListener('click', function () {
                state.comp = c.getAttribute('data-comp');
                // Jump to the first day that has this competition, if the current one has none.
                var cur = root.querySelector('.week-panel[data-day="' + state.day + '"]');
                var has = cur && (!state.comp || cur.querySelector('.week-comp[data-comp="' + state.comp + '"]'));
                if (!has) {
                    for (var i = 0; i < panels.length; i++) {
                        if (!state.comp || panels[i].querySelector('.week-comp[data-comp="' + state.comp + '"]')) { state.day = panels[i].getAttribute('data-day'); break; }
                    }
                }
                render();
            });
        });
        render();
    })();
    </script>
    @endpush
@endif
