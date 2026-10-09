@php($tabUrl = fn ($t) => route('sport.team.tab', [$currentLanguage, $team, $t]))
@php($nextMatch = $next->first())
<div class="dash">
    <section class="card team-summary dash-wide">
        <div class="stat">
            <span class="stat-label">{{ __('football.position') }}</span>
            <span class="stat-value">{{ $teamRow?->rank ?? '—' }}</span>
        </div>
        <div class="stat">
            <span class="stat-label">{{ __('football.points') }}</span>
            <span class="stat-value">{{ $teamRow?->points ?? '—' }}</span>
        </div>
        <div class="stat">
            <span class="stat-label">{{ __('football.played') }}</span>
            <span class="stat-value">{{ $teamRow?->played ?? '—' }}</span>
        </div>
        <div class="stat">
            <span class="stat-label">{{ __('football.form') }}</span>
            <span class="stat-value form-row">
                @forelse ($form as $r)
                    <span class="form-badge is-{{ strtolower($r) }}" title="{{ __('football.outcome_' . $r) }}">{{ __('football.' . ['W' => 'win', 'D' => 'draw', 'L' => 'loss'][$r]) }}</span>
                @empty
                    —
                @endforelse
            </span>
        </div>
    </section>

    <section class="card">
        <div class="card-head">
            <h2>{{ __('football.next_match') }}</h2>
            <a class="card-more" href="{{ $tabUrl('fixtures') }}">{{ __('football.all_fixtures') }} →</a>
        </div>
        @if ($next->isEmpty())
            <p class="empty">{{ __('football.no_fixtures') }}</p>
        @else
            <div class="match-list">
                @foreach ($next as $f)
                    @include('football.partials.match-row', ['f' => $f, 'showDate' => true, 'showComp' => true])
                @endforeach
            </div>
            @if ($next->contains(fn ($f) => $f->odd))
                <p class="odds-note">{{ __('football.odds_note') }}</p>
            @endif
        @endif
    </section>

    <section class="card">
        <div class="card-head">
            <h2>{{ __('football.recent_results') }}</h2>
            <a class="card-more" href="{{ $tabUrl('results') }}">{{ __('football.all_results') }} →</a>
        </div>
        @if ($recent->isEmpty())
            <p class="empty">{{ __('football.no_results') }}</p>
        @else
            <div class="match-list">
                @foreach ($recent as $i => $f)
                    @include('football.partials.match-row', ['f' => $f, 'showDate' => true, 'showComp' => true, 'outcome' => $form[$form->count() - 1 - $i] ?? null])
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
        <div class="card-head">
            <h2>{{ __('football.tab_standings') }}</h2>
            <a class="card-more" href="{{ $tabUrl('standings') }}">{{ __('football.full_table') }} →</a>
        </div>
        @include('football.partials.table', [
            'tableGroups' => $tableRows->isEmpty() ? collect() : collect(['' => $tableRows]),
            'compact'     => true,
            'highlight'   => (int) $team->api_id,
        ])
    </section>

    @if (config('football.news_enabled'))
        <section class="card">
            <div class="card-head">
                <h2>{{ __('football.news') }}</h2>
                <a class="card-more" href="{{ $tabUrl('news') }}">{{ __('football.all_news') }} →</a>
            </div>
            @include('football.partials.news-cards', ['news' => $news])
        </section>
    @else
        <section class="card">
            <div class="card-head">
                <h2>{{ __('football.tab_squad') }}</h2>
                <a class="card-more" href="{{ $tabUrl('squad') }}">{{ __('football.full_squad') }} →</a>
            </div>
            @if ($squadCount === 0)
                <p class="empty">{{ __('football.no_squad') }}</p>
            @else
                <ul class="squad-summary">
                    @if ($team->coach_name)
                        <li><span>{{ __('football.coach') }}</span><strong>{{ $team->coach_name }}</strong></li>
                    @endif
                    @foreach ($squadLines as $line => $count)
                        <li><span>{{ __('football.pos_' . $line) }}</span><strong>{{ $count }}</strong></li>
                    @endforeach
                    <li><span>{{ __('football.players_total') }}</span><strong>{{ $squadCount }}</strong></li>
                </ul>
            @endif
        </section>
    @endif
</div>
