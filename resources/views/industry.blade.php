@extends('layouts.app')

@php($industryName = $industry->translate('name'))
@php($regionName = $currentRegion->translate('name'))

@section('title', $industryName . ' · ' . $regionName . ' · ' . __('messages.site_name'))
@section('meta_description', $industryName . ' — ' . $regionName)

@section('content')
    @include('partials.breadcrumbs')

    <header class="page-head">
        <h1>{{ $industryName }}</h1>
        @if ($desc = $industry->translate('description'))
            <p class="lead">{{ $desc }}</p>
        @endif
    </header>

    @include('partials.quick-search')

    <section class="home-section">
        <h2>{{ __('messages.browse_categories') }}</h2>
        <div class="category-grid">
            @foreach ($categories as $category)
                <a class="category-tile" href="{{ route('category.show', [$currentRegion, $currentLanguage, $industry, $category]) }}">
                    {{ $category->translate('name') }}
                </a>
            @endforeach
        </div>
    </section>
@endsection
