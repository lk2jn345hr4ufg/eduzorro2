@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@php($dateLabel = $date->translatedFormat('j F Y'))

@section('title', \App\Support\Seo::pageMeta('football_countries', 'title', __('sport.football') . ' · ' . __('sport.countries') . ' · ' . __('messages.site_name')))
@section('meta_description', \App\Support\Seo::pageMeta('football_countries', 'description', __('sport.football') . ' — ' . __('sport.countries')))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ __('sport.football') }}</h1>
        <p class="lead">{{ __('sport.matchday_lead') }}</p>
    </header>

    {{-- Day strip: three days either side of the selected one. The count lets
         a visitor see an empty day before clicking into it. --}}
    <nav class="day-strip" aria-label="{{ __('sport.choose_day') }}">
        @foreach ($days as $day)
            @php($iso = $day->toDateString())
            @php($count = $counts[$iso] ?? 0)
            <a class="day-cell {{ $iso === $date->toDateString() ? 'is-active' : '' }} {{ $count ? '' : 'is-empty' }}"
               href="{{ route('sport.football.countries', [$currentLanguage]) }}?date={{ $iso }}">
                <span class="day-name">
                    {{ $day->isToday() ? __('sport.today') : $day->translatedFormat('D') }}
                </span>
                <span class="day-num">{{ $day->format('d.m') }}</span>
                @if ($count)
                    <span class="day-count">{{ $count }}</span>
                @endif
            </a>
        @endforeach
    </nav>

    <div class="football-layout">
        <main class="football-main">
            @forelse ($groups as $code => $matches)
                @php($first = $matches->first())
                <section class="competition-block">
                    <h2 class="competition-head">
                        {{ $first->league_name ?: $code }}
                        <span class="competition-count">{{ $matches->count() }}</span>
                    </h2>

                    <ul class="match-list">
                        @foreach ($matches as $match)
                            @php($played = in_array($match->status_short, ['FT', 'AET', 'PEN'], true))
                            @php($live = in_array($match->status_short, ['IN_PLAY', 'PAUSED', 'LIVE'], true))

                            <li class="match-row {{ $live ? 'is-live' : '' }}">
                                <span class="match-time">
                                    @if ($live)
                                        <span class="live-dot" aria-hidden="true"></span>{{ __('sport.live') }}
                                    @elseif ($played)
                                        {{ __('sport.finished_short') }}
                                    @else
                                        {{ optional($match->kickoff_at)->format('H:i') }}
                                    @endif
                                </span>

                                <span class="match-team match-home">
                                    @if ($match->home_logo)
                                        <img src="{{ $match->home_logo }}" alt="" width="18" height="18" loading="lazy">
                                    @endif
                                    {{ $match->home_name }}
                                </span>

                                <span class="match-score">
                                    @if ($played || $live)
                                        <strong>{{ $match->goals_home ?? '-' }}:{{ $match->goals_away ?? '-' }}</strong>
                                    @else
                                        <span class="match-vs">–</span>
                                    @endif
                                </span>

                                <span class="match-team match-away">
                                    @if ($match->away_logo)
                                        <img src="{{ $match->away_logo }}" alt="" width="18" height="18" loading="lazy">
                                    @endif
                                    {{ $match->away_name }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @empty
                <p class="no-matches">{{ __('sport.no_matches_on', ['date' => $dateLabel]) }}</p>
            @endforelse
        </main>

        <aside class="football-side">
            <section class="side-block">
                <h2>{{ __('sport.countries') }}</h2>
                <ul class="side-list">
                    @foreach ($countries as $country)
                        <li>
                            <a href="{{ route('sport.football.country', [$currentLanguage, $country]) }}">
                                {{ $country->translate('name') }}
                                <span class="side-count">{{ $country->teams_count }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="side-block">
                <h2>{{ __('sport.sports_news') }}</h2>
                <p class="side-note">
                    <a href="{{ route('sport.news.index', [$currentLanguage]) }}">
                        {{ __('sport.latest_news') }} →
                    </a>
                </p>
            </section>
        </aside>
    </div>
@endsection
