@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@section('title', \App\Support\Seo::pageMeta('sport_index', 'title', __('sport.sports') . ' · ' . __('messages.site_name')))
@section('meta_description', \App\Support\Seo::pageMeta('sport_index', 'description', __('sport.tagline')))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ __('sport.sports') }}</h1>
        <p class="lead"><a href="{{ route('sport.news.index', [$currentLanguage]) }}">{{ __('sport.sports_news') }} →</a></p>
    </header>

    <section class="home-section">
        <div class="category-grid">
            @forelse ($sports as $sport)
                <a class="category-tile"
                   href="{{ $sport->slug === 'football'
                        ? route('sport.football.countries', [$currentLanguage])
                        : route('sport.show', [$currentLanguage, $sport]) }}">
                    {{ $sport->translate('name') }}
                </a>
            @empty
                <p>{{ __('sport.no_news') }}</p>
            @endforelse
        </div>
    </section>
@endsection
