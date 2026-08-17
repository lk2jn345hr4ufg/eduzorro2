@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@php($teamName = $team->translate('name'))

@section('title', $teamName . ' · ' . __('sport.' . str_replace('-', '_', $tab)) . ' · ' . __('messages.site_name'))
@section('meta_description', $teamName . ' — ' . $country->translate('name'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head team-head">
        @if ($team->logo_url)
            <img class="team-logo" src="{{ $team->logo_url }}" alt="" width="56" height="56">
        @endif
        <div>
            <h1>{{ $teamName }}</h1>
            <p class="lead">
                {{ $country->translate('name') }}
                @if ($team->stadium) · {{ __('sport.stadium') }}: {{ $team->stadium }} @endif
                @if ($team->founded) · {{ __('sport.founded') }}: {{ $team->founded }} @endif
            </p>
        </div>
    </header>

    <nav class="team-tabs">
        @foreach ($tabs as $t)
            <a class="team-tab {{ $t === $tab ? 'is-active' : '' }}"
               href="{{ $t === 'news'
                    ? route('sport.team', [$currentRegion, $currentLanguage, $country, $team])
                    : route('sport.team.tab', [$currentRegion, $currentLanguage, $country, $team, $t]) }}">
                {{ __('sport.' . str_replace('-', '_', $t)) }}
            </a>
        @endforeach
    </nav>

    <section class="home-section team-tab-panel">
        @if (! empty($apiMissing))
            <p class="notice">{{ __('sport.data_unavailable') }}</p>
        @else
            @include('sport.partials.' . $tab)
        @endif
    </section>
@endsection
