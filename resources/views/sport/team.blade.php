@extends('layouts.app')

@php($teamName = $team->translate('name'))
@php($tabKey = str_replace('-', '_', $tab))
@php($competitionName = $competition?->translate('name') ?? '')
@php($countryName = $competition?->country?->translate('name') ?? $team->country?->translate('name') ?? '')
@php($tabLabel = __('football.tab_' . $tabKey))
@php($tokens = [
    'team'        => $teamName,
    'competition' => $competitionName,
    'country'     => $countryName,
    'site'        => __('messages.site_name'),
    'tab'         => $tabLabel,
])

{{-- Each tab is its own URL, so tags resolve in order: this team's per-tab
     override → the team-wide override → the global per-tab template
     (SEO → Meta tags) → the generated default. --}}
@php($defaultTitle = \App\Support\Seo::pageMeta(
    'team_' . $tabKey, 'title',
    $tab === 'dashboard'
        ? $teamName . ' · ' . __('messages.site_name')
        : $teamName . ' · ' . $tabLabel . ' · ' . __('messages.site_name'),
    $tokens
))
@php($defaultDescription = \App\Support\Seo::pageMeta(
    'team_' . $tabKey, 'description',
    $teamName . ($competitionName ? ' — ' . $competitionName : '') . ': ' . mb_strtolower($tabLabel),
    $tokens
))
@php($defaultHeading = \App\Support\Seo::pageMeta(
    'team_' . $tabKey, 'heading',
    $tab === 'dashboard' ? $teamName : $teamName . ': ' . mb_strtolower($tabLabel),
    $tokens
))

@section('title', $team->tabMeta($tab, 'title', $defaultTitle))
@section('meta_description', $team->tabMeta($tab, 'description', $defaultDescription))

@section('content')
    @include('partials.breadcrumbs')

    <header class="entity-head">
        @if ($team->logo_url)
            <img class="entity-logo" src="{{ $team->logo_url }}" alt="" width="64" height="64">
        @endif
        <div>
            <h1>{{ $team->tabMeta($tab, 'heading', $defaultHeading) }}</h1>
            <p class="entity-sub">
                @if ($competition && $competition->is_active)
                    <a class="pill" href="{{ route('competition.show', [$currentLanguage, $competition]) }}">{{ $competition->flag }} {{ $competitionName }}</a>
                @elseif ($countryName)
                    <span class="pill">{{ $countryName }}</span>
                @endif
                @if ($team->stadium) <span>{{ __('football.stadium') }}: {{ $team->stadium }}</span> @endif
                @if ($team->founded) <span>· {{ __('football.founded') }}: {{ $team->founded }}</span> @endif
            </p>
        </div>
    </header>

    <nav class="tabs" aria-label="{{ $teamName }}">
        @foreach ($tabs as $t)
            <a @class(['tab', 'is-active' => $t === $tab])
               href="{{ $t === 'dashboard' ? route('sport.team', [$currentLanguage, $team]) : route('sport.team.tab', [$currentLanguage, $team, $t]) }}">
                {{ __('football.tab_' . str_replace('-', '_', $t)) }}
            </a>
        @endforeach
    </nav>

    @if (! empty($apiMissing))
        <section class="card"><p class="empty">{{ __('sport.data_unavailable') }}</p></section>
    @else
        @include('football.team.' . $tab)
    @endif
@endsection
