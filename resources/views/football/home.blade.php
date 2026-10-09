@extends('layouts.app')

@php($tokens = ['site' => __('messages.site_name')])
@section('title', \App\Support\Seo::pageMeta('home', 'title', __('football.home_title') . ' · ' . __('messages.site_name'), $tokens))
@section('meta_description', \App\Support\Seo::pageMeta('home', 'description', __('football.home_lead'), $tokens))

@section('content')
    <header class="hero">
        <h1>{{ \App\Support\Seo::pageMeta('home', 'heading', __('football.home_title'), $tokens) }}</h1>
        <p class="lead">{{ __('football.home_lead') }}</p>
    </header>

    <div class="dash">
        <section class="card dash-wide">
            <div class="card-head">
                <h2>{{ __('football.championships') }}</h2>
            </div>
            @if ($leagues->isEmpty())
                <p class="empty">{{ __('football.no_data') }}</p>
            @else
                <div class="comp-grid">
                    @foreach ($leagues as $league)
                        @php($leader = $leaders->get($league->code))
                        <a class="comp-card" href="{{ route('competition.show', [$currentLanguage, $league]) }}">
                            <span class="comp-card-top">
                                @if ($league->emblem_url)
                                    <img src="{{ $league->emblem_url }}" alt="" width="36" height="36" loading="lazy">
                                @endif
                                <span>
                                    <strong>{{ $league->translate('name') }}</strong>
                                    <span class="comp-country">{{ $league->flag }} {{ $league->country?->translate('name') }}</span>
                                </span>
                            </span>
                            <span class="comp-card-foot">
                                @if ($leader)
                                    <span class="comp-leader" title="{{ __('football.leader') }}">
                                        🏆 {{ $leader->team_name }} · {{ $leader->points }}
                                    </span>
                                @else
                                    <span class="comp-leader is-muted">—</span>
                                @endif
                                @if ($n = $today->get($league->code))
                                    <span class="badge">{{ __('football.today_count', ['count' => $n]) }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card">
            <div class="card-head">
                <h2>{{ __('football.upcoming_matches') }}</h2>
            </div>
            @if ($upcoming->isEmpty())
                <p class="empty">{{ __('football.no_fixtures') }}</p>
            @else
                <div class="match-list">
                    @foreach ($upcoming as $f)
                        @include('football.partials.match-row', ['f' => $f, 'showDate' => true, 'showComp' => true])
                    @endforeach
                </div>
                @if ($upcoming->contains(fn ($f) => $f->odd))
                    <p class="odds-note">{{ __('football.odds_note') }}</p>
                @endif
            @endif
        </section>

        <section class="card">
            <div class="card-head">
                <h2>{{ __('football.tournaments') }}</h2>
            </div>
            @if ($cups->isEmpty())
                <p class="empty">{{ __('football.no_data') }}</p>
            @else
                <ul class="comp-list">
                    @foreach ($cups as $cup)
                        @php($leader = $leaders->get($cup->code))
                        <li>
                            <a href="{{ route('competition.show', [$currentLanguage, $cup]) }}">
                                @if ($cup->emblem_url)
                                    <img src="{{ $cup->emblem_url }}" alt="" width="28" height="28" loading="lazy">
                                @else
                                    <span class="flag">{{ $cup->flag }}</span>
                                @endif
                                <span class="comp-list-name">{{ $cup->translate('name') }}</span>
                                @if ($n = $today->get($cup->code))
                                    <span class="badge">{{ __('football.today_count', ['count' => $n]) }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card dash-wide">
            <div class="card-head">
                <h2>{{ __('football.popular_teams') }}</h2>
            </div>
            @include('football.partials.team-grid', ['teams' => $popular, 'showComp' => true])
        </section>

        @if (config('football.news_enabled'))
        <section class="card dash-wide">
            <div class="card-head">
                <h2>{{ __('football.latest_news') }}</h2>
                <a class="card-more" href="{{ route('sport.news.index', [$currentLanguage]) }}">{{ __('football.all_news') }} →</a>
            </div>
            @include('football.partials.news-cards', ['news' => $news, 'showTeam' => true])
        </section>
        @endif
    </div>
@endsection
