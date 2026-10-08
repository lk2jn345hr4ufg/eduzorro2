@extends('layouts.app')

@php($name = $competition->translate('name'))
@php($tabKey = str_replace('-', '_', $tab))
@php($countryName = $competition->country?->translate('name') ?? '')
@php($seasonLabel = $season . '/' . substr((string) ($season + 1), -2))
@php($tokens = [
    'competition' => $name,
    'country'     => $countryName,
    'season'      => $seasonLabel,
    'site'        => __('messages.site_name'),
    'tab'         => __('football.tab_' . $tabKey),
])

{{-- Resolution order: this competition's per-tab override → global
     per-tab template (SEO → Meta tags) → generated default. --}}
@php($defaultTitle = \App\Support\Seo::pageMeta(
    'competition_' . $tabKey, 'title',
    $tab === 'dashboard'
        ? $name . ' ' . $seasonLabel . ' · ' . __('messages.site_name')
        : $name . ' · ' . __('football.tab_' . $tabKey) . ' ' . $seasonLabel . ' · ' . __('messages.site_name'),
    $tokens
))
@php($defaultDescription = \App\Support\Seo::pageMeta(
    'competition_' . $tabKey, 'description',
    $name . ($countryName ? ' (' . $countryName . ')' : '') . ': ' . mb_strtolower(__('football.tab_' . $tabKey)) . ', ' . __('football.season', ['season' => $seasonLabel]),
    $tokens
))
@php($defaultHeading = \App\Support\Seo::pageMeta(
    'competition_' . $tabKey, 'heading',
    $tab === 'dashboard' ? $name : $name . ': ' . mb_strtolower(__('football.tab_' . $tabKey)),
    $tokens
))

@section('title', $competition->tabMeta($tab, 'title', $defaultTitle))
@section('meta_description', $competition->tabMeta($tab, 'description', $defaultDescription))

@section('content')
    @include('partials.breadcrumbs')

    <header class="entity-head">
        @if ($competition->emblem_url)
            <img class="entity-logo" src="{{ $competition->emblem_url }}" alt="" width="64" height="64">
        @endif
        <div>
            <h1>{{ $competition->tabMeta($tab, 'heading', $defaultHeading) }}</h1>
            <p class="entity-sub">
                <span class="pill">{{ $competition->isLeague() ? __('football.league') : __('football.cup') }}</span>
                @if ($competition->flag) <span class="flag">{{ $competition->flag }}</span> @endif
                @if ($countryName) {{ $countryName }} · @endif
                {{ __('football.season', ['season' => $seasonLabel]) }}
            </p>
        </div>
    </header>

    <nav class="tabs" aria-label="{{ $name }}">
        @foreach ($tabs as $t)
            <a @class(['tab', 'is-active' => $t === $tab])
               href="{{ $t === 'dashboard' ? route('competition.show', [$currentLanguage, $competition]) : route('competition.tab', [$currentLanguage, $competition, $t]) }}">
                {{ __('football.tab_' . str_replace('-', '_', $t)) }}
            </a>
        @endforeach
    </nav>

    @include('football.competition.' . $tab)
@endsection
