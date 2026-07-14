@extends('layouts.app')

@php($regionName = $region->translate('name'))
@php($language = request()->route('language'))

@section('title', ($category ? $category->name . ' · ' : '') . $verticalLabel . ' · ' . $regionName . ' · ' . __('messages.site_name'))
@section('meta_description', $verticalLabel . ' — ' . $regionName . '. ' . __('messages.tagline'))

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ $category ? $category->name : $verticalLabel }} · {{ $regionName }}</h1>
        @if ($category)
            <p class="lead">{{ $verticalLabel }}</p>
        @endif
    </header>

    @include('partials.quick-search')

    @if ($industries->isNotEmpty())
        <nav class="related" style="margin:20px 0;" aria-label="{{ __('messages.all_industries') }}">
            <ul class="chip-list">
                @foreach ($industries as $industry)
                    <li>
                        <a class="chip @if ($industry['slug'] === $vertical) chip-active @endif" href="{{ route('directory.index', [$region, $language, $industry['slug']]) }}">
                            {{ $industry['label'] }} <small>({{ number_format($industry['count']) }})</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if ($categories->isNotEmpty())
        {{-- Every category link below is a plain <a href> in the raw HTML —
             fully crawlable and functional with no JS at all. category-select.js
             only repositions these same nodes into a searchable dropdown; it
             never generates links from JSON, so there's nothing for a crawler
             to miss. --}}
        <div class="related category-select"
             data-category-select
             data-search-placeholder="{{ __('messages.search_placeholder') }}"
             data-all-label="{{ __('messages.all_industries') }}"
             style="margin:20px 0;">
            <ul class="chip-list">
                @if ($category)
                    <li>
                        <a class="chip" href="{{ route('directory.index', [$region, $language, $vertical]) }}">
                            {{ __('messages.all_industries') }}
                        </a>
                    </li>
                @endif
                @foreach ($categories as $cat)
                    <li>
                        <a class="chip @if ($category && $category->id === $cat->id) chip-active @endif" href="{{ route('directory.category', [$region, $language, $vertical, $cat->slug]) }}">
                            {{ $cat->name }} <small>({{ number_format($cat->listings_count) }})</small>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        @once
            @push('scripts')
                <script src="{{ asset('js/category-select.js') }}" defer></script>
            @endpush
        @endonce
    @endif

    <div class="listing-toolbar">
        <span class="result-count">{{ $listings->total() }} {{ __('messages.results_for') }} “{{ $category ? $category->name : $verticalLabel }}”</span>

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
