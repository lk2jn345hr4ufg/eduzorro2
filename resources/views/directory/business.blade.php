@extends('layouts.app')

@php($regionName = $region->translate('name'))

@section('title', $business->name . ' · ' . $regionName . ' · ' . __('messages.site_name'))
@section('meta_description', $business->name . ' — ' . ($business->address ?? $regionName))

@section('content')
    @include('partials.breadcrumbs')

    <article class="company-profile">
        <header class="company-header">
            <h1>{{ $business->name }}</h1>
            @if ($business->short_name && $business->short_name !== $business->name)
                <p class="lead">{{ $business->short_name }}</p>
            @endif
        </header>

        <div class="company-columns">
            <section class="company-section">
                <h2>{{ __('messages.contacts') }}</h2>
                <ul class="contact-list">
                    @if ($business->address)
                        <li><strong>{{ __('messages.address') }}:</strong> {{ $business->address }}</li>
                    @endif
                    @if ($business->phones)
                        <li><strong>☎</strong> {{ $business->phones }}</li>
                    @endif
                    @if ($business->email)
                        <li><strong>✉</strong> <a href="mailto:{{ $business->email }}">{{ $business->email }}</a></li>
                    @endif
                    @if ($business->website)
                        <li>
                            <a class="btn" href="{{ $business->website }}" target="_blank" rel="nofollow noopener">
                                {{ __('messages.visit_website') }} ↗
                            </a>
                        </li>
                    @endif
                </ul>
            </section>

            @if ($business->latitude && $business->longitude)
                <section class="company-section">
                    <h2>{{ __('messages.map') }}</h2>
                    <div id="map"
                         class="map"
                         data-lat="{{ $business->latitude }}"
                         data-lng="{{ $business->longitude }}"
                         data-label="{{ e($business->name) }}"></div>
                </section>
            @endif
        </div>

        <section class="company-section">
            <h2>Registry details</h2>
            <ul class="contact-list">
                @if ($business->edrpou)<li><strong>EDRPOU:</strong> {{ $business->edrpou }}</li>@endif
                @if ($business->director)<li><strong>Director:</strong> {{ $business->director }}</li>@endif
                @if ($business->registration_date)<li><strong>Registered:</strong> {{ $business->registration_date }}</li>@endif
                @if ($business->kved_codes)<li><strong>KVED:</strong> {{ $business->kved_codes }}</li>@endif
            </ul>
        </section>

        @if ($business->taxonomyTerms->isNotEmpty())
            <section class="company-section related">
                <h2>{{ __('messages.related_categories') }}</h2>
                <ul class="chip-list">
                    @foreach ($business->taxonomyTerms as $term)
                        <li><span class="chip">{{ $term->name }}</span></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </article>
@endsection

@push('scripts')
    @if ($business->latitude && $business->longitude)
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" defer></script>
        <script defer>
            window.addEventListener('load', function () {
                var el = document.getElementById('map');
                if (!el || typeof L === 'undefined') return;
                var lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
                var map = L.map(el).setView([lat, lng], 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors', maxZoom: 19
                }).addTo(map);
                L.marker([lat, lng]).addTo(map).bindPopup(el.dataset.label);
            });
        </script>
    @endif
@endpush
