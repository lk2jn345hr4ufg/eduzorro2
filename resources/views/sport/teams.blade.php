@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@section('title', $country->translate('name') . ' · ' . __('sport.teams') . ' · ' . __('messages.site_name'))
@section('meta_description', $country->translate('name') . ' — ' . __('sport.teams'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ $country->translate('name') }}</h1>
        <p class="lead">{{ __('sport.teams') }}</p>
    </header>

    <section class="home-section">
        <div class="category-grid">
            @forelse ($teams as $team)
                <a class="category-tile team-tile"
                   href="{{ route('sport.team', [$currentRegion, $currentLanguage, $country, $team]) }}">
                    @if ($team->logo_url)
                        <img src="{{ $team->logo_url }}" alt="" width="28" height="28" loading="lazy">
                    @endif
                    {{ $team->translate('name') }}
                </a>
            @empty
                <p>{{ __('sport.no_fixtures') }}</p>
            @endforelse
        </div>
    </section>
@endsection
