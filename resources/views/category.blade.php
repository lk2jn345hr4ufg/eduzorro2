@extends('layouts.app')

@php($categoryName = $category->translate('name'))
@php($regionName = $currentRegion->translate('name'))

@section('title', $categoryName . ' · ' . $regionName . ' · ' . __('messages.site_name'))
@section('meta_description', $categoryName . ' — ' . $regionName . '. ' . __('messages.tagline'))

@push('head')
    <script type="application/ld+json">
        {!! json_encode(
            \App\Support\Seo::itemList(
                collect($companies->items())->map(fn ($c) => route('company.show', [$currentRegion, $currentLanguage, $c]))->all()
            ),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) !!}
    </script>
@endpush

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ $categoryName }}</h1>
        @if ($desc = $category->translate('description'))
            <p class="lead">{{ $desc }}</p>
        @endif
    </header>

    @include('partials.quick-search')

    <div class="listing-toolbar">
        <span class="result-count">{{ $companies->total() }} {{ __('messages.results_for') }} “{{ $categoryName }}”</span>

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
        @forelse ($companies as $company)
            @include('partials.company-card')
        @empty
            <p class="empty">{{ __('messages.no_companies') }}</p>
        @endforelse
    </div>

    <div class="pagination-wrap">
        {{ $companies->links('pagination.simple') }}
    </div>

    @if ($relatedCategories->isNotEmpty())
        <section class="related">
            <h2>{{ __('messages.related_categories') }}</h2>
            <ul class="chip-list">
                @foreach ($relatedCategories as $rel)
                    <li>
                        <a class="chip" href="{{ route('category.show', [$currentRegion, $currentLanguage, $industry, $rel]) }}">
                            {{ $rel->translate('name') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
