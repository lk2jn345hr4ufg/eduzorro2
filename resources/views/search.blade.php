@extends('layouts.app')

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

    <div class="company-list">
        @forelse ($companies as $company)
            @include('partials.company-card')
        @empty
            <p class="empty">{{ __('messages.no_results') }}</p>
        @endforelse
    </div>

    @if ($companies instanceof \Illuminate\Contracts\Pagination\Paginator || $companies instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="pagination-wrap">{{ $companies->links('pagination.simple') }}</div>
    @endif
@endsection
