@php($tabUrl = fn ($t) => route('competition.tab', [$currentLanguage, $competition, $t]))
<div class="dash">
    <section class="card">
        <div class="card-head">
            <h2>{{ __('football.tab_standings') }}</h2>
            <a class="card-more" href="{{ $tabUrl('standings') }}">{{ __('football.full_table') }} →</a>
        </div>
        @include('football.partials.table', ['compact' => true])
    </section>

    <section class="card">
        <div class="card-head">
            <h2>{{ __('football.upcoming_matches') }}</h2>
            <a class="card-more" href="{{ $tabUrl('fixtures') }}">{{ __('football.all_fixtures') }} →</a>
        </div>
        @if ($upcoming->isEmpty())
            <p class="empty">{{ __('football.no_fixtures') }}</p>
        @else
            <div class="match-list">
                @foreach ($upcoming as $f)
                    @include('football.partials.match-row', ['f' => $f, 'showDate' => true])
                @endforeach
            </div>
            @if ($upcoming->contains(fn ($f) => $f->odd))
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
                @foreach ($recent as $f)
                    @include('football.partials.match-row', ['f' => $f, 'showDate' => true])
                @endforeach
            </div>
        @endif
    </section>

    <section class="card">
        <div class="card-head">
            <h2>{{ __('football.latest_news') }}</h2>
            <a class="card-more" href="{{ route('sport.news.index', [$currentLanguage]) }}">{{ __('football.all_news') }} →</a>
        </div>
        @include('football.partials.news-cards', ['news' => $news, 'showTeam' => true])
    </section>

    @if ($competition->isLeague())
        <section class="card dash-wide">
            <div class="card-head">
                <h2>{{ __('football.tab_teams') }}</h2>
                <span class="card-more is-muted">{{ __('football.teams_count', ['count' => $teams->count()]) }}</span>
            </div>
            @include('football.partials.team-grid', ['teams' => $teams])
        </section>
    @endif
</div>
