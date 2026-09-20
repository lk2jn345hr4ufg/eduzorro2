@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/sport.css') }}">
@endpush

@php($teamName = $team->translate('name'))

@php($tabKey = str_replace('-', '_', $tab))
@php($countryName = $country->translate('name'))
@php($tokens = ['team' => $teamName, 'country' => $countryName, 'site' => __('messages.site_name'), 'tab' => __('sport.' . $tabKey)])

{{-- Each of the five team tabs is its own URL, so the tags resolve in order:
     this team's per-tab override → the team-wide override → the global
     per-tab template (SEO → Meta tags) → the generated default. --}}
@php($defaultTitle = \App\Support\Seo::pageMeta(
    'team_' . $tabKey, 'title',
    $teamName . ' · ' . __('sport.' . $tabKey) . ' · ' . __('messages.site_name'),
    $tokens
))
@php($defaultDescription = \App\Support\Seo::pageMeta(
    'team_' . $tabKey, 'description',
    $teamName . ' — ' . $countryName,
    $tokens
))

@php($defaultHeading = \App\Support\Seo::pageMeta('team_' . $tabKey, 'heading', $teamName, $tokens))
@php($heading = $team->tabMeta($tab, 'heading', $defaultHeading))

@section('title', $team->tabMeta($tab, 'title', $defaultTitle))
@section('meta_description', $team->tabMeta($tab, 'description', $defaultDescription))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head team-head">
        @if ($team->logo_url)
            <img class="team-logo" src="{{ $team->logo_url }}" alt="" width="56" height="56">
        @endif
        <div>
            <h1>{{ $heading }}</h1>
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
                    ? route('sport.team', [$currentLanguage, $country, $team])
                    : route('sport.team.tab', [$currentLanguage, $country, $team, $t]) }}">
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
