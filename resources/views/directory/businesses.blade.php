@extends('layouts.app')

@php($regionName = $region->translate('name'))
@php($language = request()->route('language'))

@section('title', __('messages.all_businesses') . ' · ' . $regionName . ' · ' . __('messages.site_name'))
@section('meta_description', __('messages.all_businesses') . ' — ' . $regionName)

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ __('messages.all_businesses') }} · {{ $regionName }}</h1>
    </header>

    <form method="get" class="quick-search" style="max-width:420px;">
        <div class="quick-search-field">
            <input type="text" name="q" value="{{ $q }}" placeholder="{{ __('messages.search_placeholder') }}">
        </div>
        <button type="submit">{{ __('messages.search') }}</button>
    </form>

    <div class="listing-toolbar">
        <span class="result-count">{{ number_format($businesses->total()) }} {{ __('messages.results_for') }}</span>
    </div>

    <div class="company-list">
        @forelse ($businesses as $business)
            @include('partials.business-card', ['business' => $business, 'region' => $region, 'language' => $language])
        @empty
            <p class="empty">{{ __('messages.no_results') }}</p>
        @endforelse
    </div>

    <div class="pagination-wrap">
        {{ $businesses->links('pagination.simple') }}
    </div>
@endsection
