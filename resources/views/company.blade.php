@extends('layouts.app')

@php($regionName = $currentRegion->translate('name'))

@section('title', $company->name . ' · ' . $regionName . ' · ' . __('messages.site_name'))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($company->translate('description') ?? $company->name), 155))

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script type="application/ld+json">
        {!! json_encode(
            \App\Support\Seo::localBusiness($company, $averageRating, $reviewsCount),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ) !!}
    </script>
@endpush

@section('content')
    @include('partials.breadcrumbs')

    <article class="company-profile">
        <header class="company-header">
            <div>
                <h1>{{ $company->name }}</h1>
                <div class="company-meta">
                    @include('partials.stars', ['rating' => $averageRating, 'count' => $reviewsCount])
                    @if ($company->is_verified)
                        <span class="badge badge-verified">{{ __('messages.verified') }}</span>
                    @endif
                    <span class="badge badge-type">{{ $company->type === 'digital' ? __('messages.digital') : __('messages.local') }}</span>
                </div>
            </div>
        </header>

        @if ($desc = $company->translate('description'))
            <section class="company-section">
                <h2>{{ __('messages.about') }}</h2>
                <div class="prose">{!! nl2br(e($desc)) !!}</div>
            </section>
        @endif

        <div class="company-columns">
            <section class="company-section">
                <h2>{{ __('messages.contacts') }}</h2>
                <ul class="contact-list">
                    @if ($company->address)
                        <li><strong>{{ __('messages.address') }}:</strong> {{ $company->address }}</li>
                    @endif
                    @if ($company->phone)
                        <li><strong>☎</strong> <a href="tel:{{ $company->phone }}">{{ $company->phone }}</a></li>
                    @endif
                    @if ($company->email)
                        <li><strong>✉</strong> <a href="mailto:{{ $company->email }}">{{ $company->email }}</a></li>
                    @endif
                    @if ($company->website)
                        <li>
                            <a class="btn" href="{{ $company->website }}" target="_blank" rel="nofollow noopener">
                                {{ __('messages.visit_website') }} ↗
                            </a>
                        </li>
                    @endif
                </ul>

                <p class="available-in">
                    <strong>{{ __('messages.available_in') }}:</strong>
                    {{ $company->regions->map(fn ($r) => $r->translate('name'))->join(', ') }}
                </p>
            </section>

            @if ($company->latitude && $company->longitude)
                <section class="company-section">
                    <h2>{{ __('messages.map') }}</h2>
                    <div id="map"
                         class="map"
                         data-lat="{{ $company->latitude }}"
                         data-lng="{{ $company->longitude }}"
                         data-label="{{ e($company->name) }}"></div>
                </section>
            @endif
        </div>

        {{-- Reviews --}}
        <section class="company-section" id="reviews">
            <h2>{{ __('messages.reviews') }} ({{ $reviewsCount }})</h2>

            @forelse ($reviews as $review)
                <article class="review">
                    <div class="review-head">
                        @include('partials.stars', ['rating' => $review->rating])
                        <span class="review-author">{{ $review->author_name }}</span>
                        <time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('M j, Y') }}</time>
                    </div>
                    @if ($review->title)<h3 class="review-title">{{ $review->title }}</h3>@endif
                    <p>{{ $review->body }}</p>
                </article>
            @empty
                <p class="empty">{{ __('messages.no_reviews') }}</p>
            @endforelse
        </section>

        {{-- Review form --}}
        <section class="company-section" id="write-review">
            <h2>{{ __('messages.write_review') }}</h2>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="post" action="{{ route('review.store', [$currentRegion, $currentLanguage, $company]) }}" class="review-form">
                @csrf
                <div class="form-row">
                    <label>{{ __('messages.your_name') }}
                        <input type="text" name="author_name" value="{{ old('author_name') }}" required maxlength="120">
                    </label>
                    <label>{{ __('messages.your_email') }}
                        <input type="email" name="author_email" value="{{ old('author_email') }}" maxlength="190">
                    </label>
                </div>

                <label class="rating-field">{{ __('messages.rating') }}
                    <select name="rating" required>
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected(old('rating') == $i)>{{ $i }} ★</option>
                        @endfor
                    </select>
                </label>

                <label>{{ __('messages.review_title') }}
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="150">
                </label>

                <label>{{ __('messages.review_body') }}
                    <textarea name="body" rows="5" required minlength="10" maxlength="5000">{{ old('body') }}</textarea>
                </label>

                <button type="submit" class="btn btn-primary">{{ __('messages.submit_review') }}</button>
            </form>
        </section>

        {{-- Related companies --}}
        @if ($related->isNotEmpty())
            <section class="company-section related">
                <h2>{{ __('messages.related_companies') }}</h2>
                <div class="company-list">
                    @foreach ($related as $relatedCompany)
                        @include('partials.company-card', ['company' => $relatedCompany])
                    @endforeach
                </div>
            </section>
        @endif
    </article>
@endsection

@push('scripts')
    @if ($company->latitude && $company->longitude)
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
