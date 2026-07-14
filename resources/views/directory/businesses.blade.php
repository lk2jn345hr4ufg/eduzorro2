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
            <article class="company-card">
                <div class="company-card-head">
                    <h3><a href="{{ route('business.show', [$region, $language, $business]) }}">{{ $business->name }}</a></h3>
                </div>
                @if ($business->address)
                    <p class="company-card-address">{{ $business->address }}</p>
                @endif
                @if ($business->edrpou)
                    <p class="company-card-address">EDRPOU: {{ $business->edrpou }}</p>
                @endif
                <a class="btn-link" href="{{ route('business.show', [$region, $language, $business]) }}">{{ __('messages.view_profile') }} →</a>
            </article>
        @empty
            <p class="empty">{{ __('messages.no_results') }}</p>
        @endforelse
    </div>

    <div class="pagination-wrap">
        {{ $businesses->links('pagination.simple') }}
    </div>
@endsection
