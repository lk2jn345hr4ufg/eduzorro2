@extends('layouts.app')

@php($language = request()->route('language'))

@section('title', __('messages.search_results') . ' · ' . __('messages.site_name'))
@section('meta_description', __('messages.tagline'))
{{-- Search result pages should not be indexed. --}}
@push('head')<meta name="robots" content="noindex,follow">@endpush

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ __('messages.search_results') }}</h1>
        @if ($term !== '')
            <p class="lead">{{ __('messages.results_for') }} “{{ $term }}”</p>
        @endif
    </header>

    @include('partials.quick-search')

    @if ($term !== '' && $listings->isEmpty() && $businesses->isEmpty())
        <p class="empty">{{ __('messages.no_results') }}</p>
    @endif

    @if ($listings->isNotEmpty())
        <section class="home-section">
            <h2>{{ __('messages.listings') }}</h2>
            <div class="company-list">
                @foreach ($listings as $listing)
                    @php($urlSlug = array_flip(array_map(fn ($v) => $v[0], \App\Http\Controllers\DirectoryController::VERTICALS))[$listing->vertical])
                    @include('partials.listing-card', ['listing' => $listing, 'region' => $currentRegion, 'language' => $language, 'vertical' => $urlSlug])
                @endforeach
            </div>
        </section>
    @endif

    @if ($businesses->isNotEmpty())
        <section class="home-section">
            <h2>{{ __('messages.all_businesses') }}</h2>
            <div class="company-list">
                @foreach ($businesses as $business)
                    @include('partials.business-card', ['business' => $business, 'region' => $currentRegion, 'language' => $language])
                @endforeach
            </div>
        </section>
    @endif
@endsection
