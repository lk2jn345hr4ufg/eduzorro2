@extends('layouts.app')

@php($regionName = $region->translate('name'))
@php($language = request()->route('language'))

@section('title', $verticalLabel . ' · ' . $regionName . ' · ' . __('messages.site_name'))
@section('meta_description', $verticalLabel . ' — ' . $regionName . '. ' . __('messages.tagline'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ $verticalLabel }} · {{ $regionName }}</h1>
    </header>

    <div class="listing-toolbar">
        <span class="result-count">{{ $listings->total() }} {{ __('messages.results_for') }} “{{ $verticalLabel }}”</span>

        <form method="get" class="sort-form">
            <label for="sort">{{ __('messages.sort_by') }}</label>
            <select id="sort" name="sort" onchange="this.form.submit()">
                <option value="rating"  @selected($sort === 'rating')>{{ __('messages.sort_rating') }}</option>
                <option value="reviews" @selected($sort === 'reviews')>{{ __('messages.sort_reviews') }}</option>
                <option value="name"    @selected($sort === 'name')>{{ __('messages.sort_name') }}</option>
                <option value="newest"  @selected($sort === 'newest')>{{ __('messages.sort_newest') }}</option>
            </select>
        </form>
    </div>

    <div class="company-list">
        @forelse ($listings as $listing)
            @include('partials.listing-card', ['listing' => $listing, 'language' => $language])
        @empty
            <p class="empty">{{ __('messages.no_companies') }}</p>
        @endforelse
    </div>

    <div class="pagination-wrap">
        {{ $listings->links('pagination.simple') }}
    </div>
@endsection
