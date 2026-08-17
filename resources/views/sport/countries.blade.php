@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@section('title', __('sport.football') . ' · ' . __('sport.countries') . ' · ' . __('messages.site_name'))
@section('meta_description', __('sport.football') . ' — ' . __('sport.countries'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ __('sport.football') }}</h1>
        <p class="lead">{{ __('sport.countries') }}</p>
    </header>

    <section class="home-section">
        <div class="category-grid">
            @forelse ($countries as $country)
                <a class="category-tile"
                   href="{{ route('sport.football.country', [$currentRegion, $currentLanguage, $country]) }}">
                    {{ $country->translate('name') }}
                    <small>{{ $country->teams_count }} {{ __('sport.teams_count') }}</small>
                </a>
            @empty
                <p>{{ __('sport.no_fixtures') }}</p>
            @endforelse
        </div>
    </section>
@endsection
