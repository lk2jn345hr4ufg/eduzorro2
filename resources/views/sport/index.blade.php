@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@section('title', __('sport.sports') . ' · ' . $currentRegion->translate('name') . ' · ' . __('messages.site_name'))
@section('meta_description', __('sport.sports') . ' — ' . $currentRegion->translate('name'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ __('sport.sports') }}</h1>
        <p class="lead"><a href="{{ route('sport.news.index', [$currentRegion, $currentLanguage]) }}">{{ __('sport.sports_news') }} →</a></p>
    </header>

    <section class="home-section">
        <div class="category-grid">
            @forelse ($sports as $sport)
                <a class="category-tile"
                   href="{{ $sport->slug === 'football'
                        ? route('sport.football.countries', [$currentRegion, $currentLanguage])
                        : route('sport.show', [$currentRegion, $currentLanguage, $sport]) }}">
                    {{ $sport->translate('name') }}
                </a>
            @empty
                <p>{{ __('sport.no_news') }}</p>
            @endforelse
        </div>
    </section>
@endsection
