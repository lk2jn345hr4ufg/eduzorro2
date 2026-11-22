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
        <section class="card dash-wide today-card" id="today">
            @include('football.partials.home-today')
        </section>

        <section class="card dash-wide">
            <div class="card-head">
                <h2>{{ __('football.championships_and_cups') }}</h2>
            </div>
            @if ($leagues->isEmpty() && $cups->isEmpty())
                <p class="empty">{{ __('football.no_data') }}</p>
            @else
                <div class="comp-grid">
                    @foreach ($leagues as $competition)
                        @include('football.partials.comp-card', ['competition' => $competition])
                    @endforeach
                </div>

                @if ($cups->isNotEmpty())
                    <h3 class="comp-subhead">{{ __('football.cups_heading') }}</h3>
                    <div class="comp-grid">
                        {{-- Club competitions first, then national teams. --}}
                        @foreach ($cups->sortBy(fn ($c) => [(int) $c->is_national, $c->sort_order]) as $competition)
                            @include('football.partials.comp-card', ['competition' => $competition])
                        @endforeach
                    </div>
                @endif
            @endif
        </section>

        <section class="card dash-wide" id="week">
            <div class="card-head">
                <h2>{{ __('football.week_schedule') }}</h2>
                <span class="card-more is-muted">{{ __('football.week_count', ['count' => $days->sum(fn ($g) => $g->flatten()->count())]) }}</span>
            </div>
            @include('football.partials.week-schedule', ['weekDays' => $days, 'mode' => 'upcoming'])
        </section>

        <section class="card dash-wide" id="results">
            <div class="card-head">
                <h2>{{ __('football.recent_results_week') }}</h2>
                <span class="card-more is-muted">{{ __('football.week_count', ['count' => $pastDays->sum(fn ($g) => $g->flatten()->count())]) }}</span>
            </div>
            @include('football.partials.week-schedule', ['weekDays' => $pastDays, 'mode' => 'results'])
        </section>

        @if ($nationalGroups->isNotEmpty())
            <section class="card dash-wide" id="national-groups">
                <div class="card-head">
                    <h2>{{ __('football.national_groups') }}</h2>
                </div>
                @include('football.partials.home-national-groups')
            </section>
        @endif

        @if ($leaderTables->isNotEmpty())
            <section class="card dash-wide" id="leaders">
                <div class="card-head">
                    <h2>{{ __('football.championship_leaders') }}</h2>
                </div>
                @include('football.partials.home-leaders')
            </section>
        @endif

        @if ($scorers->isNotEmpty())
            <section class="card dash-wide" id="scorers">
                <div class="card-head">
                    <h2>{{ __('football.top_scorers') }}</h2>
                </div>
                @include('football.partials.home-scorers')
            </section>
        @endif

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
