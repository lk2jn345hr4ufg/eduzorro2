{{-- Expects $listing, $region, $language (Language model), $vertical (url slug) --}}
<article class="company-card">
    <div class="company-card-head">
        <h3><a href="{{ route('directory.show', [$region, $language, $vertical, $listing]) }}">{{ $listing->name }}</a></h3>
        @if ($listing->editorial_rating)
            <span class="badge badge-type">★ {{ number_format($listing->editorial_rating, 1) }}</span>
        @endif
    </div>

    @if ($listing->specialization)
        <p class="company-card-address">{{ $listing->specialization }}</p>
    @endif

    @if ($listing->description)
        <p class="company-card-desc">{{ \Illuminate\Support\Str::limit(strip_tags($listing->description), 140) }}</p>
    @endif

    @include('partials.stars', ['rating' => $listing->average_rating ?? 0, 'count' => $listing->reviews_count ?? 0])

    <div class="company-card-links">
        <a class="btn-link" href="{{ route('directory.show', [$region, $language, $vertical, $listing]) }}">{{ __('messages.view_profile') }} →</a>
        @if ($listing->website)
            <a class="btn-link" href="{{ $listing->website }}" target="_blank" rel="nofollow noopener">{{ __('messages.website') }} ↗</a>
        @endif
    </div>
</article>
