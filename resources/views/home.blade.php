@extends('layouts.app')

@section('title', __('messages.site_name') . ' — ' . __('messages.tagline'))
@section('meta_description', __('messages.tagline'))

@section('content')
    <section class="hero">
        <h1>{{ __('messages.site_name') }}</h1>
        <p class="lead">{{ __('messages.tagline') }}</p>
    </section>

    {{-- Regions: each region links to every available language (crawlable hub),
         plus a direct shortcut into each industry so visitors can skip
         straight past the language-picker step if they don't need it. --}}
    <section class="home-section">
        <h2>{{ __('messages.all_regions') }}</h2>
        <div class="region-grid">
            @foreach ($regions as $region)
                <div class="region-card">
                    <h3>{{ $region->translate('name') }}</h3>
                    <ul class="lang-links">
                        @foreach ($region->languages as $language)
                            <li>
                                <a href="{{ route('region.home', [$region, $language]) }}">
                                    {{ $language->native_name ?? $language->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    @php($extra = $regionExtras[$region->id] ?? null)

                    @if ($extra && ($extra['verticals']->isNotEmpty() || $extra['businessCount'] > 0))
                        {{-- Real WordPress-imported content for this region --}}
                        <div class="region-card-industries">
                            <p class="mini-eyebrow">{{ __('messages.all_industries') }}</p>
                            <ul class="chip-list">
                                @foreach ($extra['verticals'] as $vertical)
                                    <li>
                                        <a class="chip" href="{{ route('directory.index', [$region, $extra['linkLanguage'], $vertical['slug']]) }}">
                                            {{ $vertical['label'] }} <small>({{ number_format($vertical['count']) }})</small>
                                        </a>
                                    </li>
                                @endforeach
                                @if ($extra['businessCount'] > 0)
                                    <li>
                                        <a class="chip" href="{{ route('business.index', [$region, $extra['linkLanguage']]) }}">
                                            {{ __('messages.all_businesses') }} <small>({{ number_format($extra['businessCount']) }})</small>
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @elseif ($industries->isNotEmpty() && $languages->isNotEmpty())
                        <div class="region-card-industries">
                            <p class="mini-eyebrow">{{ __('messages.all_industries') }}</p>
                            <ul class="chip-list">
                                @foreach ($industries as $industry)
                                    <li>
                                        <a class="chip" href="{{ route('industry.show', [$region, $languages->first(), $industry]) }}">
                                            {{ $industry->translate('name') }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Languages: link each language into a region that actually supports it --}}
    <section class="home-section">
        <h2>{{ __('messages.all_languages') }}</h2>
        <ul class="language-list">
            @foreach ($languages as $language)
                @continue(! isset($languageEntryRegion[$language->id]))
                <li>
                    <a href="{{ route('region.home', [$languageEntryRegion[$language->id], $language]) }}">
                        {{ $language->native_name ?? $language->name }}
                        <small>({{ strtoupper($language->code) }})</small>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
