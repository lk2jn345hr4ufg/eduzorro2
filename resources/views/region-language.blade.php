@extends('layouts.app')

@php($regionName = $currentRegion->translate('name'))

@section('title', __('messages.site_name') . ' · ' . $regionName)
@section('meta_description', __('messages.tagline') . ' — ' . $regionName)

@section('content')
    <section class="hero hero-compact">
        <h1>{{ __('messages.site_name') }} · {{ $regionName }}</h1>
        <p class="lead">{{ __('messages.tagline') }}</p>
        @include('partials.quick-search')
    </section>

    @if ($verticals->isNotEmpty() || $businessCount > 0)
        <section class="home-section">
            <h2>{{ __('messages.browse_categories') }}</h2>
            <div class="industry-grid">
                @foreach ($verticals as $vertical)
                    <div class="industry-block">
                        <h3>
                            <a href="{{ route('directory.index', [$currentRegion, $currentLanguage, $vertical['slug']]) }}">
                                {{ $vertical['label'] }}
                            </a>
                        </h3>
                        <p class="lead" style="font-size:.85rem;margin:0;">{{ number_format($vertical['count']) }}</p>
                    </div>
                @endforeach

                @if ($businessCount > 0)
                    <div class="industry-block">
                        <h3>
                            <a href="{{ route('business.index', [$currentRegion, $currentLanguage]) }}">
                                {{ __('messages.all_businesses') }}
                            </a>
                        </h3>
                        <p class="lead" style="font-size:.85rem;margin:0;">{{ number_format($businessCount) }}</p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($industries->isNotEmpty())
        <section class="home-section">
            <h2>{{ __('messages.all_industries') }}</h2>

            <div class="industry-grid">
                @foreach ($industries as $industry)
                    <div class="industry-block">
                        <h3>
                            <a href="{{ route('industry.show', [$currentRegion, $currentLanguage, $industry]) }}">
                                {{ $industry->translate('name') }}
                            </a>
                        </h3>
                        <ul class="category-links">
                            @foreach ($industry->categories as $category)
                                <li>
                                    <a href="{{ route('category.show', [$currentRegion, $currentLanguage, $industry, $category]) }}">
                                        {{ $category->translate('name') }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
